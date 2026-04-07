<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Quotation;
use App\Models\IssuedQuotation;
use Illuminate\Auth\Access\Response;

class IssuedQuotationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Quotation $quotation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Quotation $quotation, IssuedQuotation $issuedQuotation): bool
    {
        return $this->isAllowedAs($user, $quotation);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Quotation $quotation): bool
    {
        return $this->isAllowedAs($user, $quotation);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Quotation $quotation, IssuedQuotation $issuedQuotation): bool
    {
        return $this->isAllowedAs($user, $quotation);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Quotation $quotation, IssuedQuotation $issuedQuotation): bool
    {
        return $this->isAllowedAs($user, $quotation);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, IssuedQuotation $issuedQuotation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, IssuedQuotation $issuedQuotation): bool
    {
        return false;
    }

    public function isAllowedAs($user, $quotation) : bool {
        if ($user->id === $quotation->as_id || $user->hasRole('Lead Account Specialist')) {
            return true;
        }

        return false;
    }
}
