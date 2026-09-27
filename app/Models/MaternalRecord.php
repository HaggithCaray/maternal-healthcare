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

    /**
     * Checked medical-history conditions as a plain list. Older registrations stored
     * the checkbox map ({"Hypertension": "1"}) instead of a list, so accept both.
     *
     * @return array<int, string>
     */
    public function conditions(): array
    {
        $history = $this->medical_history ?? [];

        if (array_is_list($history)) {
            return array_values(array_filter($history, 'is_string'));
        }

        return array_keys(array_filter($history));
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
