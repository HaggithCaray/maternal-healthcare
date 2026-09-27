<?php

namespace App\Models;

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

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
