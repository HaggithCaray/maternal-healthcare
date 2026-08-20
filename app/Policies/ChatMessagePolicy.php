<?php

namespace App\Policies;

use App\Models\ChatMessage;
use App\Models\User;

class ChatMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ChatMessage $message): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $message->sender_id === $user->id || $message->receiver_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }
}
