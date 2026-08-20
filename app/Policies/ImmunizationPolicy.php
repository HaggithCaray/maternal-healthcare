<?php

namespace App\Policies;

use App\Models\Immunization;
use App\Models\User;

class ImmunizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Immunization $immunization): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $childRecord = $immunization->childRecord;
        return $childRecord && $childRecord->patient && $childRecord->patient->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Immunization $immunization): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Immunization $immunization): bool
    {
        return $user->isAdmin();
    }
}
