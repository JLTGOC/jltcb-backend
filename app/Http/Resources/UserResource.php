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
        $imagePath = $this->image_path
            ? ltrim(preg_replace('#^storage/#', '', $this->image_path), '/')
            : null;

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'role' => $this->getRoleNames()->first(),
            'username' => $this->username,
            'email' => $this->email,
            'address' => $this->address,
            'contact_number' => $this->contact_number,
            'company_name' => $this->company_name,
            'image_path' => $imagePath ? asset('storage/' . $imagePath) : null,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
