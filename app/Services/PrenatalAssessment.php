<?php

namespace App\Services;

use App\Models\MaternalCheckup;
use App\Models\MaternalRecord;
use App\Models\Patient;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Gestational age, visit risk flags and scheduling for prenatal checkups.
 *
 * Thresholds: hypertension in pregnancy >= 140/90 and severe >= 160/110 (WHO/ACOG);
 * normal fetal heart rate 110–160 bpm (FIGO); post-term at 42+0 weeks.
 */
class PrenatalAssessment
{
    public const HEALTHY = 'Healthy';
    public const MONITOR = 'Monitor';
    public const HIGH_RISK = 'High Risk';

    protected const HIGH_RISK_CONDITIONS = ['Hypertension', 'Diabetes', 'Heart Disease', 'Asthma', 'Anemia', 'Multiple Births'];

    /**
     * Days since the last menstrual period on $on, or null if the LMP is unknown or after $on.
     */
    public static function gestationalAgeDays(?CarbonInterface $lmp, CarbonInterface $on): ?int
    {
        if (! $lmp) {
            return null;
        }

        $days = (int) floor($lmp->copy()->startOfDay()->diffInDays($on->copy()->startOfDay(), false));

        return $days >= 0 ? $days : null;
    }

    public static function formatGestationalAge(?int $days): ?string
    {
        return $days === null ? null : intdiv($days, 7) . 'w ' . ($days % 7) . 'd';
    }

    /**
     * Parse a recorded age of gestation such as "24w 2d" (used when no LMP is on file,
     * e.g. an ultrasound estimate sent from the field).
     */
    public static function parseGestationalAge(?string $value): ?int
    {
        if ($value === null || ! preg_match('/^\s*(\d{1,2})\s*w(?:\s*(\d)\s*d)?\s*$/i', $value, $m)) {
            return null;
        }

        return (int) $m[1] * 7 + (int) ($m[2] ?? 0);
    }

    /**
     * 1st trimester: to 13w6d, 2nd: 14w0d–27w6d, 3rd: 28w0d onward.
     */
    public static function trimester(?int $days): ?int
    {
        return match (true) {
            $days === null => null,
            $days < 14 * 7 => 1,
            $days < 28 * 7 => 2,
            default => 3,
        };
    }

    /**
     * @return array{0: int, 1: int}|null [systolic, diastolic]
     */
    public static function parseBloodPressure(?string $bp): ?array
    {
        if ($bp === null || ! preg_match('/^\s*(\d{2,3})\s*\/\s*(\d{2,3})\s*$/', $bp, $m)) {
            return null;
        }

        return [(int) $m[1], (int) $m[2]];
    }

    /**
     * Short label for a blood pressure reading: Severe, High, Low or Normal (null if unreadable).
     */
    public static function bloodPressureCategory(?string $bp): ?string
    {
        $pressure = self::parseBloodPressure($bp);
        if (! $pressure) {
            return null;
        }

        [$systolic, $diastolic] = $pressure;

        return match (true) {
            $systolic >= 160 || $diastolic >= 110 => 'Severe',
            $systolic >= 140 || $diastolic >= 90 => 'High',
            $systolic < 90 || $diastolic < 60 => 'Low',
            default => 'Normal',
        };
    }

    /**
     * Risk flags and overall status for one visit.
     *
     * @return array{status: string, flags: array<int, array{level: string, message: string}>}
     */
    public function assessVisit(?int $gestationalDays, ?string $bp, ?int $fetalHeartRate): array
    {
        $flags = [];
        $pressure = self::parseBloodPressure($bp);

        if ($pressure) {
            [$systolic, $diastolic] = $pressure;

            if ($systolic >= 160 || $diastolic >= 110) {
                $flags[] = $this->flag(self::HIGH_RISK, 'Severe hypertension (BP ≥160/110) — refer urgently.');
            } elseif ($systolic >= 140 || $diastolic >= 90) {
                $flags[] = $this->flag(self::HIGH_RISK, $gestationalDays !== null && $gestationalDays >= 20 * 7
                    ? 'High blood pressure (≥140/90) after 20 weeks — assess for pre-eclampsia.'
                    : 'High blood pressure (≥140/90) — possible chronic hypertension; refer.');
            } elseif ($systolic < 90 || $diastolic < 60) {
                $flags[] = $this->flag(self::MONITOR, 'Low blood pressure (<90/60) — monitor for dizziness or bleeding.');
            }
        }

        if ($fetalHeartRate !== null) {
            if ($fetalHeartRate < 110) {
                $flags[] = $this->flag(self::HIGH_RISK, 'Fetal heart rate below 110 bpm — refer.');
            } elseif ($fetalHeartRate > 160) {
                $flags[] = $this->flag(self::HIGH_RISK, 'Fetal heart rate above 160 bpm — refer.');
            }
        }

        if ($gestationalDays !== null && $gestationalDays >= 42 * 7) {
            $flags[] = $this->flag(self::HIGH_RISK, 'Post-term pregnancy (42 weeks or more) — refer for delivery.');
        }

        return ['status' => $this->statusFor($flags), 'flags' => $flags];
    }

    /**
     * Traditional prenatal schedule: every 4 weeks until 28 weeks, every 2 weeks until 36, then weekly.
     */
    public function nextVisitDate(CarbonInterface $visitDate, ?int $gestationalDays): Carbon
    {
        $weeks = match (true) {
            $gestationalDays === null, $gestationalDays < 28 * 7 => 4,
            $gestationalDays < 36 * 7 => 2,
            default => 1,
        };

        return Carbon::parse($visitDate)->addWeeks($weeks);
    }

    /**
     * Patient-level risk factors for the Risk Profile card.
     *
     * @return array<int, array{level: string, message: string}>
     */
    public function riskFactors(Patient $patient, ?MaternalRecord $record, ?MaternalCheckup $latestCheckup): array
    {
        $factors = [];

        $age = $patient->dob ? Carbon::parse($patient->dob)->age : null;
        if ($age !== null && $age < 18) {
            $factors[] = $this->flag(self::HIGH_RISK, "Maternal age under 18 ({$age} years).");
        } elseif ($age !== null && $age >= 35) {
            $factors[] = $this->flag(self::HIGH_RISK, "Maternal age 35 or older ({$age} years).");
        }

        if ($record) {
            if ($record->para !== null && $record->para >= 4) {
                $factors[] = $this->flag(self::HIGH_RISK, "Four or more previous births (para {$record->para}).");
            }

            if ($record->height_cm !== null && $record->height_cm > 0 && $record->height_cm < 145) {
                $factors[] = $this->flag(self::HIGH_RISK, "Height under 145 cm ({$record->height_cm} cm).");
            }

            foreach (array_intersect($record->conditions(), self::HIGH_RISK_CONDITIONS) as $condition) {
                $factors[] = $this->flag(self::HIGH_RISK, "History of {$condition}.");
            }

            if (! $record->lmp) {
                $factors[] = $this->flag(self::MONITOR, 'LMP not recorded — gestational age and due date are unknown.');
            }
        }

        foreach ($latestCheckup?->risk_flags ?? [] as $flag) {
            $factors[] = $flag;
        }

        return $factors;
    }

    /**
     * @param array<int, array{level: string, message: string}> $flags
     */
    public function statusFor(array $flags): string
    {
        $levels = array_column($flags, 'level');

        return match (true) {
            in_array(self::HIGH_RISK, $levels, true) => self::HIGH_RISK,
            in_array(self::MONITOR, $levels, true) => self::MONITOR,
            default => self::HEALTHY,
        };
    }

    /**
     * @return array{level: string, message: string}
     */
    protected function flag(string $level, string $message): array
    {
        return ['level' => $level, 'message' => $message];
    }
}
