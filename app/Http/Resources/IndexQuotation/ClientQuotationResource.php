<?php

namespace App\Http\Resources\IndexQuotation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\{
    Message,
    User,
};
use App\Models\IssuedQuotation\IssuedQuotation;

class ClientQuotationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = auth()->user();
        $platform = strtolower((string) $request->header('Platform', 'mobile'));
        $isWeb = $platform === 'web';
        $dateFormat = $isWeb ? 'Y-m-d' : 'd/m/Y';
        
        if ($request->has('filter.status')) {
            $status = null;
            if ($request->filter['status'] === 'RESPONDED') {
                if ($this->status === 'RESPONDED') {
                    $status = 'NEW';
                } else {
                    $status = $this->status;
                }
            }

            $acceptedAt = null;
            if ($this->status === 'ACCEPTED') {
                $acceptedAt = $this->updated_at;
            }

            $quotationCard = Message::where('reference_id', $this->id)
                ->where('type', 'QUOTATION_CARD')
                ->first();
            if ($quotationCard) {
                $conversationId = $quotationCard->conversation_id;
            }

            if ($status === 'ACCEPTED') {
                $shipmentCard = Message::where('reference_id', $this->shipment?->id)
                    ->where('type', 'SHIPMENT_CARD')
                    ->first();
                if ($shipmentCard) {
                    $conversationId = $shipmentCard->conversation_id;
                }
            }

            return [
                'id' => $this->id,
                'reference_number' => $this->reference_number,
                'commodity' => $this->logisticsService?->commodity ?? $this->regulatoryService?->type_of_regulatory_assistance ?? null,
                'date' => $this->created_at->format($dateFormat),
                'conversation_id' => $conversationId ?? null,
                'reassignment_request_id' => $this->latestReassignmentRequest ? $this->latestReassignmentRequest->id : null,
            ];
        }
    }
}
