<?php

namespace App\Policies;

use App\Models\ChildRecord;
use App\Models\User;

class ChildRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, ChildRecord $record): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $record->patient && $record->patient->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ChildRecord $record): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ChildRecord $record): bool
    {
        return $user->isAdmin();
    }
}
