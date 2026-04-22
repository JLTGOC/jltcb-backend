<?php

namespace App\Policies;

use App\Models\JobOrder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class JobOrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Account Specialist', 'Lead Operations', 'Operations', 'Lead Account Specialist', 'Finance']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, JobOrder $jobOrder): bool
    {
        return $user->id === $jobOrder->client_id || $user->id === $jobOrder->as_id || $user->id === $jobOrder->operations_id || $user->id === $jobOrder->finance_id || $user->hasRole(['Lead Operations', 'Lead Account Specialist']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['Account Specialist', 'Lead Account Specialist']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, JobOrder $jobOrder): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, JobOrder $jobOrder): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, JobOrder $jobOrder): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, JobOrder $jobOrder): bool
    {
        return false;
    }

    public function jobOrderEnums(User $user): bool
    {
        return $user->hasRole(['Account Specialist', 'Lead Account Specialist']);
    }

    public function showJobOrderQuotation(User $user, JobOrder $jobOrder): bool
    {
        return $user->id === $jobOrder->client_id || $user->id === $jobOrder->as_id || $user->id === $jobOrder->operations_id || $user->id === $jobOrder->finance_id || $user->hasRole(['Lead Account Specialist']);
    }

    public function acceptJobOrder(User $user, JobOrder $jobOrder): bool
    {
        if ($user->hasRole('Operations')) {
            $opsOrFinanceExists = $jobOrder->operations_id !== null;
        } elseif ($user->hasRole('Finance')) {
            $opsOrFinanceExists = $jobOrder->finance_id !== null;
        } else {
            return false;
        }
        return !$opsOrFinanceExists;
    }

    public function reassignOps(User $user, JobOrder $jobOrder): bool
    {
        return $user->hasRole('Lead Operations');
    }

    public function requestReassignment(User $user, JobOrder $jobOrder): bool
    {
        return $user->id === $jobOrder->operations_id;
    }
}
