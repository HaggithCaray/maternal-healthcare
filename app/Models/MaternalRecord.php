<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'patient_id',
    'lmp',
    'edd',
    'gravida',
    'para',
    'abortions',
    'still_births',
    'philhealth_number',
    'blood_type',
    'height_cm',
    'allergies',
    'medical_history',
    'birth_plan'
])]
class MaternalRecord extends Model
{
    protected function casts(): array
    {
        return [
            'lmp' => 'date',
            'edd' => 'date',
            'medical_history' => 'array',
            'birth_plan' => 'array',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function checkups(): HasMany
    {
        return $this->hasMany(MaternalCheckup::class)->orderBy('visit_number', 'asc');
    }
}
