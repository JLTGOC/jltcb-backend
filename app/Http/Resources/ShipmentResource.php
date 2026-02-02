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
        if ($this->cargo_volume) {
            $cargoVolume = "{$this->cargo_volume} m³";
        } else {
            $cargoVolume = null;
        }
        return [
            'referenceNumber' => $this->reference_number,
            'quotationId' => $this->quotation_id,
            'clientId' => $this->client_id,
            'accountSpecialistId' => $this->as_id,
            'status' => $this->status,
            'companyName' => $this->company_name,
            'contactPerson' => $this->contact_person,
            'contactNumber' => $this->contact_number,
            'email' => $this->email,
            'commodity' => $this->commodity,
            'cargoType' => $this->cargo_type,
            'cargoVolume' => $cargoVolume,
            'containerSize' => $this->container_size ?? null,
            'origin' => $this->origin,
            'destination' => $this->destination,
            'createdAt' => $this->created_at->format('d/m/Y'),
            'updatedAt' => $this->updated_at->format('d/m/Y'),
        ];
    }
}
