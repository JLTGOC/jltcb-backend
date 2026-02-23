<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;

class ConversationResource extends JsonResource
{
    public function __construct($resource, public $userId = null)
    {
        return parent::__construct($resource);
        $this->userId = $userId;
    }
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Allow custom user id input to be used when event calls the resource
        $user = $this->userId 
                    ? User::find($this->userId) 
                    : $request->user();

        $other = $this->participants->firstWhere('id', '!=', $user->id);

        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->type === 'GROUP' ? $this->name : ($other->full_name ?? 'User'),
            'image_path' => $this->type === 'GROUP' ? null : asset($other->image_path),
            'last_message' => $this->getLastMessageType(),
            'time' => $this->lastMessage?->created_at?->format('h:iA'),
            'unread_count' => $this->getUnreadCountFor($user),
        ];
    }
}
