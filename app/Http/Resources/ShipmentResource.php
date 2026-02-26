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
                'reference_number' => $this->reference_number,
                'quotation_id' => $this->quotation_id,
                'client' => $this->client->full_name,
                'account_specialist' => $this->accountSpecialist->full_name,
                'status' => $this->status,
                'date' => $this->created_at->format('d/m/Y'),
            ],
        ];
        
        // Only include full details if mobile OR if this is a show route
        if ($request->header('Platform', 'mobile') === 'mobile' || $request->routeIs('shipments.show')) {
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
                'created_at' => $this->created_at->format('d/m/Y'),
                'updated_at' => $this->updated_at->format('d/m/Y'),
            ];
        }

        return $data;

    }
}
