<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ShipmentSummaryResource extends JsonResource
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
            'bl_number' => $this->jobOrderShipment?->bl_no,
            'service_type' => $this->quotation?->logisticsService?->service_type,
            'transport_mode' => $this->quotation?->logisticsService?->transport_mode,
            'origin' => $this->quotation?->logisticsService?->origin,
            'destination' => $this->quotation?->logisticsService?->destination,
            'eta' => $this->jobOrderShipment?->eta,
            'etd' => $this->jobOrderShipment?->etd,
            'person_in_charge' => $this->operations?->full_name,
            'pic_image_path' => asset(Storage::url($this->operations?->image_path)),
            'status' => $this->status
        ];
    }
}
