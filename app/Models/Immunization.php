<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'child_record_id',
    'vaccine_name',
    'dose_number',
    'scheduled_date',
    'given_date',
    'administered_by',
    'remarks',
    'status'
])]
class Immunization extends Model
{
    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'given_date' => 'date',
        ];
    }

    public function childRecord(): BelongsTo
    {
        return $this->belongsTo(ChildRecord::class);
    }
}
