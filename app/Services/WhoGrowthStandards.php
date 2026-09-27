<?php

namespace App\Services;

use Carbon\CarbonInterface;

/**
 * WHO Child Growth Standards (2006) z-scores and nutritional status for children 0–60 months.
 *
 * Z-scores use the LMS method with WHO's restricted adjustment for weight-based indicators
 * beyond ±3 SD. Classification follows the WHO / DOH Operation Timbang Plus cut-offs.
 */
class WhoGrowthStandards
{
    public const MAX_AGE_DAYS = 1826; // 60 months

    // Children under 24 months are measured lying down (length), older children standing (height).
    public const LENGTH_AGE_LIMIT_DAYS = 731;

    public const NOT_ASSESSED = 'Not Assessed';
    public const RECHECK = 'Recheck Measurement';
    public const NORMAL = 'Normal';

    // Most severe first: the overall status is the first finding present.
    protected const SEVERITY = [
        'Severely Wasted',
        'Severely Underweight',
        'Severely Stunted',
        'Wasted',
        'Underweight',
        'Stunted',
        'Obese',
        'Overweight',
    ];

    /** @var array<string, array<int, array<int, array{0: float, 1: float, 2: float}>>> */
    protected static array $tables = [];

    /**
     * Assess one measurement.
     *
     * @return array{age_days: ?int, weight_for_age_z: ?float, height_for_age_z: ?float, weight_for_height_z: ?float, status: string}
     */
    public function assess(?string $gender, CarbonInterface $dob, CarbonInterface $date, float $weightKg, float $heightCm): array
    {
        $result = [
            'age_days' => null,
            'weight_for_age_z' => null,
            'height_for_age_z' => null,
            'weight_for_height_z' => null,
            'status' => self::NOT_ASSESSED,
        ];

        $sex = match (strtolower((string) $gender)) {
            'male' => 1,
            'female' => 2,
            default => null,
        };
        $ageDays = (int) floor($dob->copy()->startOfDay()->diffInDays($date->copy()->startOfDay(), false));
        $result['age_days'] = $ageDays;

        if ($sex === null || $ageDays < 0 || $ageDays > self::MAX_AGE_DAYS) {
            return $result;
        }

        $result['weight_for_age_z'] = $this->zScore('weianthro', $sex, $ageDays, $weightKg, restricted: true);
        $result['height_for_age_z'] = $this->zScore('lenanthro', $sex, $ageDays, $heightCm, restricted: false);
        $result['weight_for_height_z'] = $ageDays < self::LENGTH_AGE_LIMIT_DAYS
            ? $this->zScore('wflanthro', $sex, (int) round($heightCm * 10), $weightKg, restricted: true)
            : $this->zScore('wfhanthro', $sex, (int) round($heightCm * 10), $weightKg, restricted: true);

        $result['status'] = self::overallStatus(
            $result['weight_for_age_z'],
            $result['height_for_age_z'],
            $result['weight_for_height_z'],
        );

        return $result;
    }

    public static function weightForAgeStatus(?float $z): ?string
    {
        return match (true) {
            $z === null => null,
            $z < -3 => 'Severely Underweight',
            $z < -2 => 'Underweight',
            $z > 2 => 'Overweight',
            default => self::NORMAL,
        };
    }

    public static function heightForAgeStatus(?float $z): ?string
    {
        return match (true) {
            $z === null => null,
            $z < -3 => 'Severely Stunted',
            $z < -2 => 'Stunted',
            $z > 3 => 'Tall',
            default => self::NORMAL,
        };
    }

    public static function weightForHeightStatus(?float $z): ?string
    {
        return match (true) {
            $z === null => null,
            $z < -3 => 'Severely Wasted',
            $z < -2 => 'Wasted',
            $z > 3 => 'Obese',
            $z > 2 => 'Overweight',
            default => self::NORMAL,
        };
    }

    /**
     * Single status for the measurement: the most severe finding, or "Recheck Measurement"
     * when a z-score is outside WHO's biologically plausible range (usually a typo).
     */
    public static function overallStatus(?float $wfa, ?float $hfa, ?float $wfh): string
    {
        if ($wfa === null && $hfa === null && $wfh === null) {
            return self::NOT_ASSESSED;
        }

        $implausible = ($wfa !== null && ($wfa < -6 || $wfa > 5))
            || ($hfa !== null && ($hfa < -6 || $hfa > 6))
            || ($wfh !== null && ($wfh < -5 || $wfh > 5));

        if ($implausible) {
            return self::RECHECK;
        }

        $findings = [
            self::weightForAgeStatus($wfa),
            self::heightForAgeStatus($hfa),
            self::weightForHeightStatus($wfh),
        ];

        foreach (self::SEVERITY as $status) {
            if (in_array($status, $findings, true)) {
                return $status;
            }
        }

        return self::NORMAL;
    }

    /**
     * Z-score for measurement $x against the LMS row at $index (age in days, or length/height in mm).
     */
    protected function zScore(string $table, int $sex, int $index, float $x, bool $restricted): ?float
    {
        $lms = $this->table($table)[$sex][$index] ?? null;
        if ($lms === null || $x <= 0) {
            return null;
        }

        [$l, $m, $s] = $lms;
        $z = abs($l) < 1e-9 ? log($x / $m) / $s : (pow($x / $m, $l) - 1) / ($l * $s);

        if ($restricted && abs($z) > 3) {
            $sd = fn (int $n) => abs($l) < 1e-9 ? $m * exp($s * $n) : $m * pow(1 + $l * $s * $n, 1 / $l);

            $z = $z > 3
                ? 3 + ($x - $sd(3)) / ($sd(3) - $sd(2))
                : -3 + ($x - $sd(-3)) / ($sd(-2) - $sd(-3));
        }

        return round($z, 2);
    }

    /**
     * @return array<int, array<int, array{0: float, 1: float, 2: float}>> [sex][index] => [L, M, S]
     */
    protected function table(string $name): array
    {
        if (isset(self::$tables[$name])) {
            return self::$tables[$name];
        }

        $rows = [];
        $lines = file(resource_path("data/who-growth/{$name}.txt"), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach (array_slice($lines, 1) as $line) {
            $cols = explode("\t", trim($line));
            // Age tables are indexed by whole days; length/height tables by tenths of a cm.
            $index = str_starts_with($name, 'wf') ? (int) round((float) $cols[1] * 10) : (int) $cols[1];
            $rows[(int) $cols[0]][$index] = [(float) $cols[2], (float) $cols[3], (float) $cols[4]];
        }

        return self::$tables[$name] = $rows;
    }
}
