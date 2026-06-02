<?php

namespace App\Policies;

use App\Models\{
    User,
    Quotation,
    QuotationFile
};
use Illuminate\Auth\Access\Response;

class QuotationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Client', 'Account Specialist', 'Lead Account Specialist', 'Operations', 'Lead Operations', 'Client Success', 'Lead Client Success']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Quotation $quotation): bool
    {
        return $user->id === $quotation->client_id || $user->hasRole(['Lead Account Specialist', 'Account Specialist', 'Operations', 'Lead Operations', 'Client Success', 'Lead Client Success']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['Client', 'Lead Account Specialist', 'Lead Client Success', 'Account Specialist', 'Client Success']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Quotation $quotation): bool
    {
        return $user->id === $quotation->client_id || $user->id === $quotation->as_id || $user->hasRole(['Lead Account Specialist']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Quotation $quotation): bool
    {
        return $user->id === $quotation->client_id || $user->hasRole(['Lead Account Specialist']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Quotation $quotation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Quotation $quotation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view quotation enums.
     */
    public function enumQuotationOptions(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can upload files.
     */
    public function upload(User $user, Quotation $quotation): bool
    {
        return $user->id === $quotation->as_id || $user->hasRole(['Lead Account Specialist', 'Lead Client Success']);
    }

    /**
     * Determine whether the user can view uploaded files.
     */
    public function showFile(User $user, Quotation $quotation): bool
    {
        return $user->id === $quotation->client_id || $user->hasRole(['Lead Account Specialist', 'Account Specialist', 'Operations', 'Lead Operations', 'Client Success', 'Lead Client Success']);
    }

    /**
     * Determine whether the user can initiate chat about their quotation.
     */
    public function chatWithQuotation(User $user, Quotation $quotation) {
        return $user->id === $quotation->client_id;
    }

    public function reassignSpecialist(User $user, Quotation $quotation): bool
    {
        return $user->hasRole(['Lead Account Specialist', 'Lead Client Success', 'Client Success']);
    }

    public function requestReassignment(User $user, Quotation $quotation): bool
    {
        return $user->id === $quotation->as_id;
    }

    public function acceptQuotationProposal(User $user, Quotation $quotation): bool
    {
        return $user->id === $quotation->client_id;
    }

    public function acceptQuotationAssignment(User $user, Quotation $quotation): bool
    {
        if ($user->hasRole(['Account Specialist', 'Lead Account Specialist', 'Client Success', 'Lead Client Success'])) {
            $available = $quotation->assignment_status !== 'ASSIGNED';
        } else {
            return false;
        }
        return $available;
    }
}
