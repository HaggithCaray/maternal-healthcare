<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
    /** Contact details a child linked to its mother takes from her record. */
    public const CONTACT_FIELDS = ['phone', 'address', 'emergency_contact_name', 'emergency_contact_phone'];

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * The mother whose contact details this patient uses: set for a child linked to a registered
     * mother, so her current phone, address and emergency contact are always shown (never a copy).
     */
    public function contactMother(): ?Patient
    {
        if (($this->attributes['registration_type'] ?? null) !== 'Child') {
            return null;
        }

        return $this->childRecord?->mother;
    }

    protected function phone(): Attribute
    {
        return Attribute::get(fn ($value) => $this->contactMother()?->phone ?? $value);
    }

    protected function address(): Attribute
    {
        return Attribute::get(fn ($value) => $this->contactMother()?->address ?? $value);
    }

    protected function emergencyContactName(): Attribute
    {
        return Attribute::get(fn ($value) => $this->contactMother()?->emergency_contact_name ?? $value);
    }

    protected function emergencyContactPhone(): Attribute
    {
        return Attribute::get(fn ($value) => $this->contactMother()?->emergency_contact_phone ?? $value);
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
