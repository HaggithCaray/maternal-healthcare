<?php

namespace App\Models;

use App\Services\WhoGrowthStandards;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'child_record_id',
    'date',
    'age_months',
    'weight_kg',
    'height_cm',
    'head_circumference_cm',
    'weight_for_age_z',
    'height_for_age_z',
    'weight_for_height_z',
    'status'
])]
class GrowthMeasurement extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'weight_for_age_z' => 'float',
            'height_for_age_z' => 'float',
            'weight_for_height_z' => 'float',
        ];
    }

    /**
     * Age and WHO z-scores are always derived from the child's birth date and sex,
     * so every entry point (web form, registration, offline sync, seeder) is assessed the same way.
     */
    protected static function booted(): void
    {
        static::saving(function (GrowthMeasurement $measurement) {
            $patient = $measurement->childRecord?->patient;
            if (! $patient?->dob) {
                return;
            }

            $dob = Carbon::parse($patient->dob);
            $date = Carbon::parse($measurement->date ?? now());
            $measurement->age_months = max(0, (int) floor($dob->diffInMonths($date, false)));

            $result = app(WhoGrowthStandards::class)->assess(
                $patient->gender,
                $dob,
                $date,
                (float) $measurement->weight_kg,
                (float) $measurement->height_cm,
            );

            $measurement->weight_for_age_z = $result['weight_for_age_z'];
            $measurement->height_for_age_z = $result['height_for_age_z'];
            $measurement->weight_for_height_z = $result['weight_for_height_z'];
            $measurement->status = $result['status'];
        });
    }

    /**
     * The three WHO indicators with their z-score and classification, for display.
     *
     * @return array<int, array{label: string, z: ?float, status: ?string}>
     */
    public function indicators(): array
    {
        $underTwo = $this->age_months < 24;

        return [
            [
                'label' => 'Weight-for-age',
                'z' => $this->weight_for_age_z,
                'status' => WhoGrowthStandards::weightForAgeStatus($this->weight_for_age_z),
            ],
            [
                'label' => $underTwo ? 'Length-for-age' : 'Height-for-age',
                'z' => $this->height_for_age_z,
                'status' => WhoGrowthStandards::heightForAgeStatus($this->height_for_age_z),
            ],
            [
                'label' => $underTwo ? 'Weight-for-length' : 'Weight-for-height',
                'z' => $this->weight_for_height_z,
                'status' => WhoGrowthStandards::weightForHeightStatus($this->weight_for_height_z),
            ],
        ];
    }

    public function childRecord(): BelongsTo
    {
        return $this->belongsTo(ChildRecord::class);
    }
}
