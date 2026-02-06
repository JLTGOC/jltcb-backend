<?php

namespace App\Policies;

use App\Models\{
    Conversation,
    User,
    Quotation    
};

use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Auth;

//Policy for Conversation model
class ChatPolicy
{
    /**
     * Determine whether the user can view their inbox.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view a conversation's history.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->participants()->where('user_id', $user->id)->exists();
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
    public function update(User $user, Conversation $conversation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Conversation $conversation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Conversation $conversation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Conversation $conversation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can send message to a GROUP typed conversation.
     */
    public function sendMessageToGroup(User $user, Conversation $conversation): bool {
        return $conversation->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can send message to another user.
     */
    // public function sendMessageToUser(User $user, User $recipient) : bool {
    //     // Deny message to self and Allow only Client ↔ Account Specialist messaging
    //     return ($user->hasRole('Client') && $recipient->hasRole('Account Specialist'))
    //         || ($user->hasRole('Account Specialist') && $recipient->hasRole('Client'));
    // }

    /**
     * Determine whether the user can initiate chat about their quotation.
     */
    // public function chatWithQuotation(User $user, Quotation $quotation) {
    //     return $user->id === $quotation->client_id;
    // }
}
