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
        return [
            'referenceNumber' => $this->reference_number,
            'clientId' => $this->client_id,
            'accountSpecialistId' => $this->as_id,
            'status' => $this->status,
            'companyName' => $this->company_name,
            'companyAddress' => $this->company_address,
            'contactPerson' => $this->contact_person,
            'contactNumber' => $this->contact_number,
            'email' => $this->email,
            'serviceType' => $this->service_type,
            'serviceOptions' => explode(',', $this->service_options),
            'transportMode' => $this->transport_mode,
            'commodity' => $this->commodity,
            'cargoVolume' => $this->cargo_volume,
            'containerSize' => $this->container_size ?? null,
            'origin' => $this->origin,
            'destination' => $this->destination,
            'createdAt' => $this->created_at->format('d/m/Y'),
            'updatedAt' => $this->updated_at->format('d/m/Y'),
        ];
    }
}
