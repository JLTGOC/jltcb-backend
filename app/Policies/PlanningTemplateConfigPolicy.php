<?php

namespace App\Policies;

use App\Models\PlanningTemplateConfig;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PlanningTemplateConfigPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user): bool
    {
        return $this->isCSD($user);
    }


    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user): bool
    {
        return $this->isCSD($user);
    }

    private function isCSD(User $user) {
        return $user->hasRole('Client Success');
    }
}
