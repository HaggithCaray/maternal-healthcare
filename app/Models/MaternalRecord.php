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
     * Medical-history checklist shown on the registration and edit forms.
     */
    public const CONDITIONS = ['Hypertension', 'Diabetes', 'Asthma', 'Heart Disease', 'Anemia', 'Multiple Births'];

    /**
     * Normalize checklist input to a plain list of known conditions, e.g. ["Hypertension", "Anemia"].
     * Accepts the form's checkbox map ({"Hypertension": "1", "Asthma": "0"}) or an existing list.
     *
     * @return array<int, string>
     */
    public static function normalizeConditions(mixed $history): array
    {
        if (! is_array($history)) {
            return [];
        }

        $checked = array_is_list($history)
            ? array_filter($history, 'is_string')
            : array_keys(array_filter($history, fn ($value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)));

        return array_values(array_intersect(self::CONDITIONS, $checked));
    }

    /**
     * Checked medical-history conditions as a plain list.
     *
     * @return array<int, string>
     */
    public function conditions(): array
    {
        return self::normalizeConditions($this->medical_history);
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
