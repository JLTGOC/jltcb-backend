<?php

namespace App\Policies;

use App\Models\User;

class ConfigurationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->isAuthorized($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, $record): bool
    {
        return $this->isAuthorized($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->isAuthorized($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, $record): bool
    {
        return $this->isAuthorized($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, $record): bool
    {
        return $this->isAuthorized($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, $record): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, $record): bool
    {
        return false;
    }

    private function isAuthorized($user): bool {
        if ($user->hasRole(['Account Specialist', 'Lead Account Specialist', 'Client Success'])) {
            return true;
        }
        return false;
    }
}
