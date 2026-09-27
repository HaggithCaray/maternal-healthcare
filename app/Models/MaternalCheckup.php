<?php

namespace App\Models;

use App\Services\PrenatalAssessment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'maternal_record_id',
    'visit_number',
    'date',
    'weight_kg',
    'bp',
    'age_of_gestation',
    'fetal_heart_rate',
    'attendant',
    'status',
    'risk_flags',
    'notes',
    'next_visit_date'
])]
class MaternalCheckup extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'next_visit_date' => 'date',
            'risk_flags' => 'array',
        ];
    }

    /**
     * Gestational age, risk status and next visit are always derived from the visit data,
     * so every entry point (web form, offline sync, seeder) is assessed the same way.
     */
    protected static function booted(): void
    {
        static::saving(function (MaternalCheckup $checkup) {
            $assessment = app(PrenatalAssessment::class);
            $visitDate = Carbon::parse($checkup->date ?? now());

            $days = PrenatalAssessment::gestationalAgeDays($checkup->maternalRecord?->lmp, $visitDate)
                ?? PrenatalAssessment::parseGestationalAge($checkup->age_of_gestation);
            $checkup->age_of_gestation = PrenatalAssessment::formatGestationalAge($days);

            $result = $assessment->assessVisit($days, $checkup->bp, $checkup->fetal_heart_rate);
            $checkup->status = $result['status'];
            $checkup->risk_flags = $result['flags'];

            if (! $checkup->next_visit_date) {
                $checkup->next_visit_date = $assessment->nextVisitDate($visitDate, $days);
            }
        });

        // Escalate the patient so she stands out in Records; only staff lower the status again.
        static::saved(function (MaternalCheckup $checkup) {
            $patient = $checkup->maternalRecord?->patient;

            if ($checkup->status === PrenatalAssessment::HIGH_RISK && $patient && $patient->status !== 'High Risk') {
                $patient->update(['status' => 'High Risk']);
            }
        });
    }

    public function maternalRecord(): BelongsTo
    {
        return $this->belongsTo(MaternalRecord::class);
    }
}
