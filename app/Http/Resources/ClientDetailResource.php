<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientDetailResource extends JsonResource
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
            'position' => $this->getRoleNames()->first(),
            'contact_number' => $this->contact_number,
            'email' => $this->email,
            'date_created' => $this->created_at,
            'company_name' => $this->company_name,
            'company_address' => $this->company_address,
            'business_type' => $this->business_type,
            'quotations' => [
                'pending' => $this->quotations_pending_count,
                'accepted' => $this->quotations_accepted_count,
                'total' => $this->quotations_pending_count + $this->quotations_accepted_count
            ],
            'shipments' => [
                'in_progress' => $this->shipments_in_progress_count,
                'completed' => $this->shipments_completed_count,
                'total' => $this->shipments_in_progress_count + $this->shipments_completed_count
            ],
            'regulatory' => [
                'ongoing' => $this->regulatory_ongoing_count,
                'completed' => $this->regulatory_completed_count,
                'total' => $this->regulatory_ongoing_count + $this->regulatory_completed_count
            ],
        ];
    }
}
