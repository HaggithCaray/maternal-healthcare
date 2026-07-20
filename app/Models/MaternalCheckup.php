<?php

namespace App\Models;

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
        ];
    }

    public function maternalRecord(): BelongsTo
    {
        return $this->belongsTo(MaternalRecord::class);
    }
}
