<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Postnatal (postpartum) visit scheduling and risk flags.
 *
 * The DOH/WHO postnatal care schedule is a visit within 48 hours of delivery, then at
 * 1 week, 2 weeks and 6 weeks. Blood-pressure thresholds reuse the WHO/ACOG cutoffs
 * already used for prenatal visits (>= 140/90 and severe >= 160/110), because postpartum
 * pre-eclampsia uses the same numbers.
 */
class PostpartumAssessment
{
    /** Days after delivery at which each routine postnatal visit is due. */
    public const SCHEDULE_DAYS = [2, 7, 14, 42];

    /** Short labels for the routine visits, keyed by the day they are due. */
    public const SCHEDULE_LABELS = [
        2 => 'Within 48 hours',
        7 => '1 week',
        14 => '2 weeks',
        42 => '6 weeks',
    ];

    /**
     * Days since delivery on $on, or null when the delivery date is unknown or after $on.
     */
    public static function daysPostpartum(?CarbonInterface $deliveryDate, CarbonInterface $on): ?int
    {
        if (! $deliveryDate) {
            return null;
        }

        $days = (int) floor($deliveryDate->copy()->startOfDay()->diffInDays($on->copy()->startOfDay(), false));

        return $days >= 0 ? $days : null;
    }

    /**
     * The next routine visit due after $days, or null once the 6-week visit is covered.
     */
    public static function nextScheduledDay(?int $days): ?int
    {
        foreach (self::SCHEDULE_DAYS as $day) {
            if ($days === null || $day > $days) {
                return $day;
            }
        }

        return null;
    }

    /**
     * The date of the next routine visit, counted from the delivery date.
     * Falls back to six weeks after the current visit when no delivery date is on file.
     */
    public function nextVisitDate(?CarbonInterface $deliveryDate, CarbonInterface $visitDate): Carbon
    {
        if ($deliveryDate === null) {
            return Carbon::parse($visitDate)->addDays(42);
        }

        $days = self::daysPostpartum($deliveryDate, $visitDate);
        $nextDay = self::nextScheduledDay($days);

        return $nextDay === null
            ? Carbon::parse($deliveryDate)->addDays(42)
            : Carbon::parse($deliveryDate)->addDays($nextDay);
    }

    /**
     * Risk flags and overall status for one postnatal visit.
     *
     * @return array{status: string, flags: array<int, array{level: string, message: string}>}
     */
    public function assessVisit(?string $bp): array
    {
        $flags = [];
        $pressure = PrenatalAssessment::parseBloodPressure($bp);

        if ($pressure) {
            [$systolic, $diastolic] = $pressure;

            if ($systolic >= 160 || $diastolic >= 110) {
                $flags[] = $this->flag(PrenatalAssessment::HIGH_RISK, 'Severe hypertension (BP ≥160/110) — refer urgently for possible postpartum pre-eclampsia.');
            } elseif ($systolic >= 140 || $diastolic >= 90) {
                $flags[] = $this->flag(PrenatalAssessment::HIGH_RISK, 'High blood pressure (≥140/90) after delivery — assess for postpartum pre-eclampsia.');
            } elseif ($systolic < 90 || $diastolic < 60) {
                $flags[] = $this->flag(PrenatalAssessment::MONITOR, 'Low blood pressure (<90/60) — monitor for dizziness, bleeding or shock.');
            }
        }

        return ['status' => PrenatalAssessment::statusFor($flags), 'flags' => $flags];
    }

    /**
     * @return array{level: string, message: string}
     */
    protected function flag(string $level, string $message): array
    {
        return ['level' => $level, 'message' => $message];
    }
}
