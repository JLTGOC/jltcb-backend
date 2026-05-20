<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use App\Http\Resources\QuotationFileResource;
use Illuminate\Support\Facades\Storage;
use App\Models\ShipmentFile;
use App\Models\QuotationFile;

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
        $originalFiles = QuotationFile::whereIn('id', $shipmentFiles->pluck('quotation_file_id'))->get()->groupBy('type');
        $proposals = $originalFiles->get('PROPOSAL', collect())->take(2);
        $clientDocuments = $originalFiles->get('REQUESTED', collect())->take(2);

        $data = [
            'general_info' => [
                'id' => $this->id,
                'reference_number' => $this->reference_number,
                'job_order_id' => $this->job_order_id,
                'client' => $this->client->full_name,
                'company_name' => $this->client->company_name,
                'person_in_charge' => mb_strtoupper($this->operations?->username) . ' ' . $this->operations?->last_name,
                'person_in_charge_full_name' => $this->operations?->full_name,
                'person_in_charge_image' => $this->operations->image_path ? asset(Storage::url($this->operations->image_path)) : null,
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
                'service_level' => $this->jobOrder->jobOrderShipment->service_level ?? null,
                'transport_mode' => $this->quotation->logisticsService->transport_mode ?? null,
                'account_handler' => $this->accountSpecialist->full_name,
                'created_at' => $this->created_at->format('m/d/Y'),
                'updated_at' => $this->updated_at->format('m/d/Y'),
            ],
            'total_quotation_files' => $originalFiles->get('PROPOSAL', collect())->count(),
            'total_client_documents' => $originalFiles->get('REQUESTED', collect())->count(),
            'quotation_proposals' => QuotationFileResource::collection($proposals),
            'client_documents' => QuotationFileResource::collection($clientDocuments),
        ];
        
        // Only include full details for mobile OR if this is a show route
        if ($request->routeIs('shipments.show')) {
            $data['general_info'] = [
                'id' => $this->id,
                'reference_number' => $this->reference_number,
                'job_order_id' => $this->job_order_id,
                'client' => [
                    'full_name' => $this->client->full_name,
                    'company_name' => $this->client->company_name,
                    'contact_number' => $this->client->contact_number,
                    'email' => $this->client->email,
                    'image_path' => $this->client->image_path ? asset(Storage::url($this->client->image_path)) : null,
                ],
                'person_in_charge' => [
                    'username' => mb_strtoupper($this->operations?->username) . ' ' . $this->operations?->last_name,
                    'role' => $this->operations?->getRoleNames()->first() ?? null,
                    'full_name' => $this->operations?->full_name,
                    'image_path' => $this->operations?->image_path ? asset(Storage::url($this->operations?->image_path)) : null,
                ],
                'status' => $this->status,
                'date' => $this->created_at->format('Y-m-d'),
            ];

            $data['commodity_details'] = [
                'commodity' => $this->commodity,
                'cargo_type' => $this->cargo_type,
                'container_size' => $this->container_size ?? null,
            ];

            $data['shipment_information'] = [
                'bl_number' => $this->jobOrder->jobOrderShipment->bl_no ?? null,
                'origin' => $this->origin,
                'destination' => $this->destination,
                'eta' => (Carbon::parse($this->jobOrder->jobOrderShipment->eta))->format('F d, Y') ?? null,
                'etd' => (Carbon::parse($this->jobOrder->jobOrderShipment->etd))->format('F d, Y') ?? null,
                'sub_services' => explode(',', $this->quotation->logisticsService->service_options),
                'service_type' => $this->quotation->logisticsService->service_type ?? null,
                'service_level' => $this->jobOrder->jobOrderShipment->service_level ?? null,
                'transport_mode' => $this->quotation->logisticsService->transport_mode ?? null,
                'account_handler' => $this->accountSpecialist->full_name,
                'remarks' => $this->remarks,
            ];

            $data['consignee_details'] = [
                'company_name' => $this->company_name,
                'company_address' => $this->quotation->company_address ?? null,
                'contact_person' => $this->contact_person,
                'contact_number' => $this->contact_number,
                'email' => $this->email,
            ];

            $data['quotation_history'] = $this->quotation->quotationActivities()->with('user')->orderBy('created_at', 'desc')->get()->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'action' => $activity->action,
                    'user' => $activity->user->hasRole(['Account Specialist', 'Lead Account Specialist', 'Operations', 'Lead Operations']) 
                        ? mb_strtoupper($activity->user->username) 
                        : $activity->user->full_name,
                    'datetime' => $activity->created_at->format('F d, Y h:i A'),
                ];
            });

            $data['shipment_history'] = $this->shipmentActivities()->with('user')->orderBy('created_at', 'desc')->get()->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'action' => $activity->action,
                    'user' => $activity->user->hasRole(['Account Specialist', 'Lead Account Specialist', 'Operations', 'Lead Operations']) 
                        ? mb_strtoupper($activity->user->username) 
                        : $activity->user->full_name,
                    'datetime' => $activity->created_at->format('F d, Y h:i A'),
                ];
            });
        }

        return $data;

    }
}
