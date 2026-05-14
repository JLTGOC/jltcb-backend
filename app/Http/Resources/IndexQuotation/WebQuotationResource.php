<?php

namespace App\Http\Resources\IndexQuotation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\{
    IssuedQuotation,
    Message,
    User,
};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class WebQuotationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $dateFormat = 'F d, Y';
        $issuedQuotation = IssuedQuotation::where('quotation_id', $this->id)->value('id');
                    
        if ($this->accountSpecialist) {
            $as = (mb_strtoupper($this->accountSpecialist?->username) . ' ' . $this->accountSpecialist?->full_name);
        } else {
            $as = null;
        }

        $quotationCard = Message::where('reference_id', $this->id)
            ->where('type', 'QUOTATION_CARD')
            ->first();
        if ($quotationCard) {
            $conversationId = $quotationCard->conversation_id;
        }

        if ($this->shipment) {
            $shipmentCard = Message::where('reference_id', $this->shipment?->id)
                ->where('type', 'SHIPMENT_CARD')
                ->first();
            if ($shipmentCard) {
                $conversationId = $shipmentCard->conversation_id;
            }
        }

        $clientType = $this->client->quotations()->count() > 1 ? 'OLD' : 'NEW';

        $reassignmentRequest = $this->latestReassignmentRequest;

        $previouslyAssignedTo = $reassignmentRequest?->status === 'APPROVED' && $reassignmentRequest->as_id !== $this->as_id 
            ? mb_strtoupper($reassignmentRequest->accountSpecialist->username) . ' ' . $reassignmentRequest->accountSpecialist->last_name 
            : null;

        if ($reassignmentRequest?->status !== 'PENDING') {
            $reassignmentRequest = null;
        }
            
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'date' => $this->created_at->format($dateFormat),
            'client_full_name' => $this->client->full_name,
            'company_name' => $this->company_name,
            'client_type' => $clientType,
            'status' => $this->status,
            'assignment_status' => $this->assignment_status,
            'account_specialist' =>  $as,
            'as_profile_image' => $this->accountSpecialist->image_path ? asset(Storage::url($this->accountSpecialist?->image_path)) : null,
            'assigned_at' => $this->assigned_at ? Carbon::parse($this->assigned_at)->format($dateFormat) : null,
            'reassignment_request_id' => $reassignmentRequest ? $reassignmentRequest->id : null,
            'requested_at' => $reassignmentRequest ? Carbon::parse($reassignmentRequest->created_at)->format($dateFormat) : null,
            'previously_assigned_to' => $previouslyAssignedTo,
            'service' => $this->logisticsService ? 'LOGISTICS' : ($this->regulatoryService ? 'REGULATORY' : null),
            'logistics_service' => $this->logisticsService ? [
                'commodity' => $this->logisticsService->commodity,
                'service_type' => $this->logisticsService->service_type,
                'transport_mode' => $this->logisticsService->transport_mode,
                'origin' => $this->logisticsService->origin,
                'destination' => $this->logisticsService->destination,
            ] : null,
            'regulatory_service' => $this->regulatoryService ? [
                'application_type' => $this->regulatoryService->application_type,
            ] : null,
            'conversation_id' => $conversationId ?? null,
            'prepared_by' => $this->created_by ? User::where('id', $this->created_by)->value('full_name') : null,
            'issued_quotation_id' => $issuedQuotation ?? null,
        ];
    }
}
