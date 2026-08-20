<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Patient $patient): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($patient->user_id !== null && $patient->user_id === $user->id) {
            return true;
        }

        // Allow mothers to view their linked children
        if ($patient->registration_type === 'Child' && $patient->childRecord) {
            $mother = $patient->childRecord->mother;
            if ($mother && $mother->user_id === $user->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Patient $patient): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($patient->user_id !== null && $patient->user_id === $user->id) {
            return true;
        }

        if ($patient->registration_type === 'Child' && $patient->childRecord) {
            $mother = $patient->childRecord->mother;
            if ($mother && $mother->user_id === $user->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Patient $patient): bool
    {
        return $user->isAdmin();
    }
}
