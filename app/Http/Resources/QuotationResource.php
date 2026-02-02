<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuotationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->cargo_volume) {
            $cargoVolume = "{$this->cargo_volume} m³";
        } else {
            $cargoVolume = null;
        }
        return [
            'general_info' => [
                'reference_number' => $this->reference_number,
                'client_id' => $this->client_id,
                'account_specialist_id' => $this->as_id,
                'status' => $this->status,
            ],
            'company_details' => [
                'company_name' => $this->company_name,
                'company_address' => $this->company_address,
                'contact_person' => $this->contact_person,
                'contact_number' => $this->contact_number,
                'email' => $this->email,
            ],
            'service_details' => [
                'service_type' => $this->service_type,
                'service_options' => explode(',', $this->service_options),
                'transport_mode' => $this->transport_mode,
            ],
            'commodity_details' => [
                'commodity' => $this->commodity,
                'cargo_type' => $this->cargo_type,
                'cargo_volume' => $cargoVolume,
                'container_size' => $this->container_size ?? null,
            ],
            'shipment_details' => [
                'origin' => $this->origin,
                'destination' => $this->destination,
                'created_at' => $this->created_at->format('d/m/Y'),
                'updated_at' => $this->updated_at->format('d/m/Y'),
            ]
        ];
    }
}
