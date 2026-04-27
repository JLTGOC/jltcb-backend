<?php

namespace App\Events;

use App\Http\Resources\MessageResource;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Message;

class ChatEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    private $conversation_id;

    /**
     * Create a new event instance.
     */
    public function __construct(private Message $message, private string $clientId)
    {
        $this->conversation_id = $message->conversation_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->conversation_id)
        ];
    }

    public function broadcastAs(): string
    {
        // chat.stored, chat.updated, chat.deleted
        return 'message.sent';
    }

    public function broadcastWith()
    {
        return [
            'message' => new MessageResource($this->message),
            'client_id' => $this->clientId
        ];
    }
}