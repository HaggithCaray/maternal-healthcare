<?php

namespace App\Models;

use App\Services\PostpartumAssessment;
use App\Services\PrenatalAssessment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'maternal_record_id',
    'date',
    'weight_kg',
    'bp',
    'days_postpartum',
    'attendant',
    'status',
    'risk_flags',
    'notes',
    'next_visit_date'
])]
class PostpartumVisit extends Model
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
     * Days postpartum, risk status and the next visit are always derived from the visit data,
     * so every entry point (web form, offline sync) is assessed the same way.
     */
    protected static function booted(): void
    {
        static::saving(function (PostpartumVisit $visit) {
            $assessment = app(PostpartumAssessment::class);
            $visitDate = Carbon::parse($visit->date ?? now());
            $deliveryDate = $visit->maternalRecord?->delivery_date;

            $visit->days_postpartum = PostpartumAssessment::daysPostpartum($deliveryDate, $visitDate);

            $result = $assessment->assessVisit($visit->bp);
            $visit->status = $result['status'];
            $visit->risk_flags = $result['flags'];

            if (! $visit->next_visit_date) {
                $visit->next_visit_date = $assessment->nextVisitDate($deliveryDate, $visitDate);
            }
        });

        // Escalate the patient so she stands out in Records; only staff lower the status again.
        static::saved(function (PostpartumVisit $visit) {
            $patient = $visit->maternalRecord?->patient;

            if ($visit->status === PrenatalAssessment::HIGH_RISK && $patient && $patient->status !== 'High Risk') {
                $patient->update(['status' => 'High Risk']);
            }
        });
    }

    public function maternalRecord(): BelongsTo
    {
        return $this->belongsTo(MaternalRecord::class);
    }
}
