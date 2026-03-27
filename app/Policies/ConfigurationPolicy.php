<?php

namespace App\Policies;

use App\Models\DetailsConfiguration;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ConfigurationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->isAccountSpecialist($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, $record): bool
    {
        return $this->isAccountSpecialist($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->isAccountSpecialist($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, $record): bool
    {
        return $this->isAccountSpecialist($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, $record): bool
    {
        return $this->isAccountSpecialist($user);
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

    private function isAccountSpecialist($user): bool {
        if ($user->hasRole(['Account Specialist', 'Lead Account Specialist'])) {
            return true;
        }
        return false;
    }
}
