<?php

namespace App\Policies;

use App\Models\PlanningTimeline\Template\PlanningTemplate;
use App\Models\PlanningTimeline\Template\PlanningTemplatePhase;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PlanningTemplatePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->isCSD($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PlanningTemplate $planningTemplate): bool
    {
        return $this->isCSD($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->isCSD($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PlanningTemplate $planningTemplate): bool
    {
        return $this->isCSD($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PlanningTemplate $planningTemplate): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PlanningTemplate $planningTemplate): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PlanningTemplate $planningTemplate): bool
    {
        return false;
    }

    public function toggleStatus(User $user, PlanningTemplate $planningTemplate) : bool {
        return $this->isCSD($user);
    }

    public function updateHeadings(User $user, PlanningTemplate $template, PlanningTemplatePhase $phase) {
        return $this->isCSD($user);
    }

    private function isCSD(User $user) {
        return $user->hasRole('Client Success');
    }
}
