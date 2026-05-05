<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use App\Http\Resources\QuotationFileResource;

class ShipmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $shipmentFiles = $this->shipmentFile;

        $data = [
            'general_info' => [
                'id' => $this->id,
                'reference_number' => $this->reference_number,
                'job_order_id' => $this->job_order_id,
                'quotation_file' => asset($this->quotation->files()->where('type', 'PROPOSAL')->first()->file_path) ?? null,
                'client' => $this->client->full_name,
                'person_in_charge' => mb_strtoupper($this->operations?->username) . ' ' . $this->operations?->last_name,
                'person_in_charge_image' => $this->operations->image_path ? asset($this->operations->image_path) : null,
                'status' => $this->status,
                'commodity' => $this->commodity,
                'date' => $this->created_at->format('Y-m-d'),
            ],
            'shipment_information' => [
                'bl_number' => $this->jobOrder->jobOrderShipment->bl_no ?? null,
                'origin' => $this->origin,
                'destination' => $this->destination,
                'eta' => (Carbon::parse($this->jobOrder->jobOrderShipment->eta))->format('F d, Y') ?? null,
                'etd' => (Carbon::parse($this->jobOrder->jobOrderShipment->etd))->format('F d, Y') ?? null,
                'service_type' => $this->quotation->logisticsService->service_type ?? null,
                'transport_mode' => $this->quotation->logisticsService->transport_mode ?? null,
                'account_handler' => $this->accountSpecialist->full_name,
                'created_at' => $this->created_at->format('m/d/Y'),
                'updated_at' => $this->updated_at->format('m/d/Y'),
            ],
            'files' => QuotationFileResource::collection($shipmentFiles->pluck('quotationFile')->flatten()),
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
        }

        return $data;

    }
}
