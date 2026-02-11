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
            $volume = "LCL {$this->cargo_volume} m³";
        } elseif ($this->container_size) {
            $volume = "CONTAINERIZED {$this->container_size}";
        }
        return [
            'general_info' => [
                'reference_number' => $this->reference_number,
                'client' => $this->client->full_name,
                'account_specialist' => $this->accountSpecialist->full_name,
                'status' => $this->status,
            ],
            'consignee_details' => [
                'company_name' => $this->company_name,
                'company_address' => $this->company_address,
                'contact_person' => $this->contact_person,
                'contact_number' => $this->contact_number,
                'email' => $this->email,
            ],
            'shipment_details' => [
                'service_type' => $this->service_type,
                'transport_mode' => $this->transport_mode,
                'service' => $this->service_options,
                'commodity' => $this->commodity,
                'volume' => $volume,
                'origin' => $this->origin,
                'destination' => $this->destination,
                'details' => $this->remarks,
                'created_at' => $this->created_at->format('d/m/Y'),
                'updated_at' => $this->updated_at->format('d/m/Y'),
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
