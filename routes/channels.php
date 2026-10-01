<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// A patient's conversation with the health station: the patient and every staff member.
Broadcast::channel('patient-chat.{patientId}', function ($user, $patientId) {
    return (int) $user->id === (int) $patientId || $user->isAdmin();
});

// Unread counts for the shared inbox, so every staff page keeps its badges current.
Broadcast::channel('staff-inbox', function ($user) {
    return $user->isAdmin();
});

Broadcast::channel('online', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'is_staff' => $user->isAdmin(),
    ];
});
