<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ConversationResource extends JsonResource
{
    protected $userId;

    public function __construct($resource, $userId = null)
    {
        // Allow custom user id input to be used when event instantiates this resource
        parent::__construct($resource);
        $this->userId = $userId;
    }
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // If this resource is used by the index method, set user manually 
        $user = Auth::user(); 

        if (!$request->from_index && $this->userId) {
            $user = User::find($this->userId);
        }

        $other = $this->participants->firstWhere('id', '!=', $user->id);

        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->type === 'GROUP' ? $this->name : ($other->full_name ?? 'User'),
            'image_path' => $this->type === 'GROUP' ? null : asset($other->image_path),
            'last_message' => $this->getLastMessageType(),
            'time' => $this->lastMessage?->created_at,
            'unread_count' => $this->getUnreadCountFor($user),
        ];
    }
}
