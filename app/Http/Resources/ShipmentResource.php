<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'general_info' => [
                'id' => $this->id,
                'reference_number' => $this->reference_number,
                // 'quotation_id' => $this->quotation_id,
                'job_order_id' => $this->job_order_id,
                'quotation_file' => asset($this->quotation->files()->where('type', 'PROPOSAL')->first()->file_path) ?? null,
                'client' => $this->client->full_name,
                'status' => $this->status,
                'commodity' => $this->commodity,
                'destination' => $this->destination,
                'eta' => $this->jobOrder->eta ?? null,
                'etd' => $this->jobOrder->etd ?? null,
                'date' => $this->created_at->format('Y-m-d'),
            ],
        ];
        
        // Only include full details for mobile OR if this is a show route
        if ($request->routeIs('shipments.show')) {
            $data['commodity_details'] = [
                'commodity' => $this->commodity,
                'consignee_name' => $this->company_name,
                'cargo_type' => $this->cargo_type,
                'container_size' => $this->container_size ?? null,
            ];

            $data['contact_person'] = [
                'full_name' => $this->contact_person,
                'contact_number' => $this->contact_number,
                'email' => $this->email,
            ];
            
            $data['shipment_information'] = [
                'origin' => $this->origin,
                'destination' => $this->destination,
                'account_handler' => $this->accountSpecialist->full_name,
                'created_at' => $this->created_at->format('m/d/Y'),
                'updated_at' => $this->updated_at->format('m/d/Y'),
            ];
        }

        return $data;

    }
}
