<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sender_id',
    'receiver_id',
    'message',
    'is_read',
    'attachment_path',
    'attachment_name',
    'attachment_type'
])]
class ChatMessage extends Model
{
    protected $appends = ['attachment_url'];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    public function isImage(): bool
    {
        return $this->attachment_type && str_starts_with($this->attachment_type, 'image/');
    }

    public function isVideo(): bool
    {
        return $this->attachment_type && str_starts_with($this->attachment_type, 'video/');
    }

    public function isDocument(): bool
    {
        return $this->attachment_path && !$this->isImage() && !$this->isVideo();
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? route('messaging.attachment', $this) : null;
    }

    /**
     * A patient's conversation with the health station: everything the patient sent and every
     * reply any staff member sent them. Patients only write to staff and staff only to patients.
     */
    public function scopeThread(Builder $query, int $patientUserId): Builder
    {
        return $query->where(fn (Builder $q) => $q->where('sender_id', $patientUserId)->orWhere('receiver_id', $patientUserId));
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
