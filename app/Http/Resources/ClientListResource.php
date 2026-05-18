<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'client_name' => $this->full_name,
            'company_name' => $this->company_name,
            'email' => $this->email,
            'contact_number' => $this->contact_number,
            'type' => 'old',
            'pending_quotations' => $this->pending_quotations_count,
            'active_shipments' => $this->active_shipments_count,
            'active_regulatory' => $this->active_regulatory_count
        ];
    }
}
