<?php

namespace App\Http\Resources\IndexQuotation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\{
    Message,
    User,
};
use App\Models\IssuedQuotation\IssuedQuotation;
use Illuminate\Support\Carbon;

class MobileQuotationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = auth()->user();
        $dateFormat = 'Y/m/d';

        if ($request->has('filter.status')) {
            $quotationCard = Message::where('reference_id', $this->id)
                ->where('type', 'QUOTATION_CARD')
                ->first();
            if ($quotationCard) {
                $conversationId = $quotationCard->conversation_id;
            } else {
                $conversationId = null;
            }

            if ($this->shipment) {
                $shipmentCard = Message::where('reference_id', $this->shipment?->id)
                    ->where('type', 'SHIPMENT_CARD')
                    ->first();
                if ($shipmentCard) {
                    $conversationId = $shipmentCard->conversation_id;
                }
            }

            if ($user->hasRole('Lead Account Specialist') && $user->id !== $this->as_id) {
                $conversationId = null;
            }

            return [
                'id' => $this->id,
                'client_name' => $this->client->full_name,
                'reference_number' => $this->reference_number,
                'issued_quotation_id' => IssuedQuotation::where('quotation_id', $this->id)->value('id') ?? null,
                'commodity' => $this->logisticsService?->commodity ?? $this->regulatoryService?->type_of_regulatory_assistance ?? null,
                'date' => $this->created_at->format($dateFormat),
                'conversation_id' => $conversationId ?? null,
                'prepared_by' => $this->created_by ? User::where('id', $this->created_by)->value('full_name') : null,
                'service' => $this->logisticsService ? 'LOGISTICS' : ($this->regulatoryService ? 'REGULATORY' : null),
                'service_type' => $this->serviceType->name ?? null,
                'reassignment_request_id' => $this->latestReassignmentRequest ? $this->latestReassignmentRequest->id : null,
            ];
        }
    }
}
