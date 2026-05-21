<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
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
    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->hasRole(['Lead Account Specialist', 'Account Specialist', 'IT', 'Operations']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        if ($user->id === $model->id || $user->hasRole('IT')) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can change password.
     */
    public function changePassword(User $user, User $model): bool
    {
        // Allow if user is updating their own password or if they are an IT
        return $user->id === $model->id || $user->hasRole('IT');
    }   

    public function changeProfile(User $user, User $model): bool
    {
        // Allow if user is updating their own profile or if they are an IT
        return $user->id === $model->id || $user->hasRole('IT');
    }

    public function viewAccountsList(User $user): bool {
        return $user->hasRole(['Lead Account Specialist', 'Account Specialist', 'IT', 'Operations']);
    }
}
