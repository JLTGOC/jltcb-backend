<?php

namespace App\Http\Resources\IndexJobOrder;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\JobOrder;
use App\Models\IssuedQuotation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class WebLogisticsJobOrderResource extends JsonResource
{
    public function __construct($resource, private ?string $serviceLevel = null)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = auth()->user();

        if ($this->operations) {
            $assignedTo = mb_strtoupper($this->operations->full_name);
        } else {
            $assignedTo = null;
        }

        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'client' => $this->client->full_name,
            'client_type' => JobOrder::where('client_id', $this->client_id)->count() > 1 ? 'OLD' : 'NEW',
            'date' => $this->date_issued ? Carbon::parse($this->date_issued)->format('F d, Y') : null,
            'date_created' => strtoupper($this->created_at->format('F d, Y')),
            'job_type' => 'LOGISTICS',
            'commodity' => $this->quotation->logisticsService->commodity,
            'service_type' => $this->quotation->logisticsService->service_type,
            'transport_mode' => $this->quotation->logisticsService->transport_mode,
            'origin' => $this->quotation->logisticsService->origin,
            'destination' => $this->quotation->logisticsService->destination,
            'service_level' => $this->serviceLevel,
            'bl_no' => $this->jobOrderShipment->bl_no ?? null,
            'eta' => $this->jobOrderShipment->eta ? Carbon::parse($this->jobOrderShipment->eta)->format('M d, Y') : null,
            'etd' => $this->jobOrderShipment->etd ? Carbon::parse($this->jobOrderShipment->etd)->format('M d, Y') : null,
            'quotation_id' => $this->quotation_id,
            'quotation_reference_number' => $this->quotation->reference_number,
            'issued_quotation_id' => IssuedQuotation::where('quotation_id', $this->quotation_id)->value('id'),
            'assignment_status' => $this->assignment_status,
            'assigned_to' => $assignedTo,
            'ops_id' => $this->operations_id,
            'ops_image' => $this->operations ? asset(Storage::url($this->operations->image_path)) : null,
            'assigned_at' => $this->operations_id ? mb_strtoupper(Carbon::parse($this->assigned_at)->format('F d, Y')) : null,
            'reassignment_request_id' => $this->latestReassignmentRequest?->status !== 'PENDING' ? null : $this->latestReassignmentRequest->id,
            'requested_at' => $this->latestReassignmentRequest?->status === 'PENDING' ? Carbon::parse($this->latestReassignmentRequest?->created_at)->format('F d, Y') : null,
            'previously_assigned_to' => $this->latestReassignmentRequest?->status === 'APPROVED' && $this->latestReassignmentRequest?->operations
                ? mb_strtoupper($this->latestReassignmentRequest?->operations?->username) . ' ' . $this->latestReassignmentRequest?->operations?->last_name 
                : null,
            'generate_shipment' => $this->operations_id === $user->id && !$this->shipment && $this->assignment_status === 'ASSIGNED' ? true : false,
            'shipment_creation_status' => $this->shipment_creation_status,
        ];
    }
}
