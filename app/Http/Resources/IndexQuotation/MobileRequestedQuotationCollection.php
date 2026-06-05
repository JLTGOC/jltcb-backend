<?php

namespace App\Http\Resources\IndexQuotation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Models\{
    Message,
    User,
};
use App\Models\IssuedQuotation\IssuedQuotation;
use Illuminate\Support\Carbon;

class MobileRequestedQuotationCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = auth()->user();
        $dateFormat = 'Y/m/d';
        $client = $this->collection->first()->client;

        return [
            'client_id' => $client?->id ?? null,
            'client_full_name' => $client?->full_name ?? null,
            'quotations_count' => $this->collection->count(),
            'date' => $this->collection->first()->created_at->format($dateFormat),
            'quotations' => $this->collection->map(function ($quotation) use ($dateFormat, $user) {
                $issuedQuotation = IssuedQuotation::where('quotation_id', $quotation->id)->value('id');

                $quotationCard = Message::where('reference_id', $quotation->id)
                    ->where('type', 'QUOTATION_CARD')
                    ->first();
                if ($quotationCard) {
                    $conversationId = $quotationCard->conversation_id;
                } else {
                    $conversationId = null;
                }

                if ($quotation->shipment) {
                    $shipmentCard = Message::where('reference_id', $quotation->shipment?->id)
                        ->where('type', 'SHIPMENT_CARD')
                        ->first();
                    if ($shipmentCard) {
                        $conversationId = $shipmentCard->conversation_id;
                    }
                }

                if ($user->hasRole('Lead Account Specialist') && $user->id !== $quotation->as_id) {
                    $conversationId = null;
                }

                $reassignmentRequest = $quotation->latestReassignmentRequest;
                if ($reassignmentRequest && $reassignmentRequest->status !== 'PENDING') {
                    $reassignmentRequest = null;
                }

                return [
                    'id' => $quotation->id,
                    'reference_number' => $quotation->reference_number,
                    'date' => $quotation->created_at->format($dateFormat),
                    'client_full_name' => $quotation->client?->full_name ?? null,
                    'company_name' => $quotation->company?->name ?? null,
                    'status' => $quotation->status,
                    'assignment_status' => $quotation->assignment_status,
                    'as_username' => $quotation->accountSpecialist?->username ?? 'Available',
                    'as_full_name' => $quotation->accountSpecialist?->full_name ?? null,
                    'assigned_at' => $quotation->assigned_at ? Carbon::parse($quotation->assigned_at)->format($dateFormat) : null,
                    'reassignment_request_id' => $reassignmentRequest ? $reassignmentRequest->id : null,
                    'requested_at' => $reassignmentRequest ? Carbon::parse($reassignmentRequest->created_at)->format($dateFormat) : null,
                    'service' => $quotation->logisticsService ? 'LOGISTICS' : ($quotation->regulatoryService ? 'REGULATORY' : null),
                    'logistics_service' => $quotation->logisticsService ? [
                        'commodity' => $quotation->logisticsService->commodity,
                        'service_type' => $quotation->logisticsService->service_type,
                        'transport_mode' => $quotation->logisticsService->transport_mode,
                        'origin' => $quotation->logisticsService->origin,
                        'destination' => $quotation->logisticsService->destination,
                    ] : null,
                    'regulatory_service' => $quotation->regulatoryService ? [
                        'application_type' => $quotation->regulatoryService->application_type,
                    ] : null,
                    'conversation_id' => $conversationId ?? null,
                    'prepared_by' => $quotation->created_by ? User::where('id', $quotation->created_by)->value('full_name') : null,
                    'issued_quotation_id' => $issuedQuotation ?? null,
                ];
            })->values(),
        ];
    }
}
