<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ClientListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'client_id' => $this->id,
            'profile_image' => asset(Storage::url($this->image_path)),
            'client_name' => $this->full_name,
            'company_name' => $this->company?->name ?? null,
            'email' => $this->email,
            'contact_number' => $this->contact_number,
            'type' => $this->quotations_count > 1 ? 'OLD' : 'NEW',
            'pending_quotations' => $this->pending_quotations_count,
            'active_shipments' => $this->active_shipments_count,
            'active_regulatory' => $this->active_regulatory_count
        ];
    }
}
