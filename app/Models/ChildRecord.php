<?php

namespace App\Models;

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
    'has_hepb_at_birth'
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
