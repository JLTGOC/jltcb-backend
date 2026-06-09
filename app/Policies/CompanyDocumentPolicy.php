<?php

namespace App\Policies;

use App\Models\CompanyDocument;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CompanyDocumentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, $company, $companyDocument): bool
    {
        return $user->hasRole(['Lead Account Specialist', 'Account Specialist', 'Operations', 'Client Success']) || $user->companies()->where('company_id', $company->id)->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CompanyDocument $companyDocument): bool
    {
        return $user->hasRole(['Lead Account Specialist', 'Account Specialist', 'Operations', 'Client Success']) || $user->companies()->where('company_id', $companyDocument->company_id)->exists();
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
    public function update(User $user, CompanyDocument $companyDocument): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CompanyDocument $companyDocument): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CompanyDocument $companyDocument): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CompanyDocument $companyDocument): bool
    {
        return false;
    }
}
