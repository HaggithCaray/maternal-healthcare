<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'first_name',
    'last_name',
    'dob',
    'gender',
    'phone',
    'email',
    'address',
    'barangay',
    'occupation',
    'emergency_contact_name',
    'emergency_contact_phone',
    'registration_type',
    'status'
])]
class Patient extends Model
{
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function maternalRecord(): HasOne
    {
        return $this->hasOne(MaternalRecord::class);
    }

    public function childRecord(): HasOne
    {
        return $this->hasOne(ChildRecord::class);
    }

    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class);
    }
}
