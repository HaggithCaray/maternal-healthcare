<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $ids = explode('-', $conversationId);
    return in_array($user->id, $ids);
});

Broadcast::channel('online', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});

