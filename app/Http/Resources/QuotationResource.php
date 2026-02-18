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
            'reference_number' => $this->reference_number,
            'client' => $this->client->full_name,
            'account_specialist' => $this->accountSpecialist->full_name,
            'status' => $this->status,
            'created_at' => $this->created_at->format('m/d/Y'),
            'updated_at' => $this->updated_at->format('m/d/Y'),
            'company' => [
                'name' => $this->company_name,
                'address' => $this->company_address,
                'contact_person' => $this->contact_person,
                'contact_number' => $this->contact_number,
                'email' => $this->email,
            ],
            'service' => [
                'type' => $this->service_type,
                'transport_mode' => $this->transport_mode,
                'options' => $this->service_options,
            ],
            'commodity' => [
                'commodity' => $this->commodity,
                'cargo_type' => $this->cargo_type,
                'container_size' => $this->container_size ?? null
            ],
            'shipment' => [
                'origin' => $this->origin,
                'destination' => $this->destination,
            ],
            'documents' => $this->files()->exists()
                ? $this->files()->get()->map(function($file) {
                    return [
                        'id' => $file->id,
                        'file_name' => $file->original_file_name,
                        'file_url' => asset($file->file_path),
                    ];
                })
                : 'No documents available.',
        ];
    }
}
