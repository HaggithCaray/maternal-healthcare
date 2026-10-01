<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'patient_id',
    'mother_id',
    'birth_weight_kg',
    'birth_height_cm',
    'head_circumference_cm',
    'birth_type',
    'delivery_type',
    'delivery_place',
    'attendant',
    'birth_order',
    'blood_type',
    'has_newborn_screening',
    'has_hearing_screening',
    'has_eye_prophylaxis',
    'has_vitamin_k',
    'has_bcg_at_birth',
    'has_hepb_at_birth',
    'allergies'
])]
class ChildRecord extends Model
{
    protected function casts(): array
    {
        return [
            'has_newborn_screening' => 'boolean',
            'has_hearing_screening' => 'boolean',
            'has_eye_prophylaxis' => 'boolean',
            'has_vitamin_k' => 'boolean',
            'has_bcg_at_birth' => 'boolean',
            'has_hepb_at_birth' => 'boolean',
        ];
    }

    /**
     * Birth-dose vaccines and the flag saying they were given at birth.
     */
    public const BIRTH_DOSES = [
        'BCG' => 'has_bcg_at_birth',
        'Hepatitis B' => 'has_hepb_at_birth',
    ];

    public const BIRTH_DOSE_REMARK = 'Given at birth';

    /**
     * Make the first BCG / Hepatitis B dose match the "given at birth" flags.
     *
     * Ticking a flag marks a pending dose as given on the birth date. Unticking it puts the dose back
     * on the schedule, but only if it was recorded through this flag: a dose given later at the
     * health station keeps its own record.
     */
    public function syncBirthDoses(): void
    {
        $dob = $this->patient?->dob;
        if (! $dob) {
            return;
        }

        foreach (self::BIRTH_DOSES as $vaccine => $flag) {
            $dose = $this->immunizations()->where('vaccine_name', $vaccine)->where('dose_number', 1)->first();
            if (! $dose) {
                continue;
            }

            if ($this->{$flag} && $dose->status !== 'Given') {
                $dose->update([
                    'status' => 'Given',
                    'given_date' => Carbon::parse($dob)->toDateString(),
                    'administered_by' => null,
                    'remarks' => self::BIRTH_DOSE_REMARK,
                ]);
            } elseif (! $this->{$flag} && $dose->status === 'Given' && str_starts_with((string) $dose->remarks, self::BIRTH_DOSE_REMARK)) {
                $dose->update([
                    'status' => 'Scheduled',
                    'given_date' => null,
                    'administered_by' => null,
                    'remarks' => null,
                ]);
            }
        }
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function mother(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'mother_id');
    }

    public function immunizations(): HasMany
    {
        return $this->hasMany(Immunization::class)->orderBy('scheduled_date', 'asc');
    }

    public function growthMeasurements(): HasMany
    {
        return $this->hasMany(GrowthMeasurement::class)->orderBy('date', 'asc');
    }
}
