<?php

namespace App\Models;

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
    'status'
])]
class GrowthMeasurement extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function childRecord(): BelongsTo
    {
        return $this->belongsTo(ChildRecord::class);
    }
}
