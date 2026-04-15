<?php

namespace App\Policies;

use App\Models\ServiceOption;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ServiceOptionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ServiceOption $serviceOption): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['Lead Account Specialist']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ServiceOption $serviceOption): bool
    {
        return $user->hasRole(['Lead Account Specialist']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ServiceOption $serviceOption): bool
    {
        return $user->hasRole(['Lead Account Specialist']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ServiceOption $serviceOption): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ServiceOption $serviceOption): bool
    {
        return false;
    }
}
