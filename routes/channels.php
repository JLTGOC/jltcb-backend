<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;
    
Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);

    return $conversation->participants()->where('user_id', $user->id)->exists();
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId; 
});


