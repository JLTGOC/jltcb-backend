<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientAccountResource extends JsonResource
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
            'full_name' => $this->full_name,
            'company_name' => $this->company_name,
            'position' => $this->position,
            'company_address' => $this->company_address,
            'business_type' => $this->business_type,
            'email' => $this->email,
            'contact_number' => $this->contact_number,
            'date_created' => $this->created_at->format('F d, Y'),
            'quotations' => [
                'pending' => $this->quotations()->whereNotIn('status', ['ACCEPTED'])->count(),
                'accepted' => $this->quotations()->where('status', 'ACCEPTED')->count(),
            ],
            'shipments' => [
                'in_progress' => $this->shipments()->whereNotIn('status', ['DELIVERED'])->count(),
                'completed' => $this->shipments()->where('status', 'DELIVERED')->count(),
            ],
            'regulatory' => [
                // placeholder for now, as we don't have a clear definition of "ongoing" and "completed" for regulatory jobs yet
                'ongoing' => $this->jobOrders()->where('job_type', 'REGULATORY')->count(),
                'completed' => $this->jobOrders()->where('job_type', 'REGULATORY')->count(),
                // 'ongoing' => $this->jobOrders()->where('job_type', 'REGULATORY')->whereNotIn('status', ['COMPLETED'])->count(),
                // 'completed' => $this->jobOrders()->where('job_type', 'REGULATORY')->where('status', 'COMPLETED')->count(),
            ],
        ];
    }
}
