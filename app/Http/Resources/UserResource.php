<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'role' => $this->getRoleNames()->first(),
            'email' => $this->email,
            'address' => $this->address,
            'contact_number' => $this->contact_number,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
