<?php

namespace App\Policies;

use App\Models\QuotationFile;
use App\Models\User;
use App\Models\Quotation;
use Illuminate\Auth\Access\Response;

class QuotationFilePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Quotation $quotation): bool
    {
        return $user->id === $quotation->client_id || $user->hasRole(['Lead Account Specialist', 'Account Specialist', 'Operations','Client Success']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Quotation $quotation, QuotationFile $quotationFile): bool
    {
        return $user->id === $quotation->client_id || $user->hasRole(['Lead Account Specialist', 'Account Specialist', 'Operations', 'Client Success']);
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
    public function update(User $user, QuotationFile $quotationFile): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, QuotationFile $quotationFile): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, QuotationFile $quotationFile): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, QuotationFile $quotationFile): bool
    {
        return false;
    }
}
