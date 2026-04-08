<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Models\{
    Message,
    Conversation,
    Shipment
};

class QuotationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $platform = strtolower((string) $request->header('Platform', 'mobile'));
        $isWeb = $platform === 'web';
        $logisticsService = $this->logisticsService;
        $regulatoryService = $this->regulatoryService;

        // Prefer explicit regulatory relation when present; otherwise default to logistics shape.
        $isRegulatory = !is_null($regulatoryService);

        $message = Message::where('reference_id', $this->id)->first();
        
        if (!$message) {
            $conversationId = null;
        } else {
            $conversationId = Conversation::where('id', $message->conversation_id)->value('id');
        }
        
        $options = [];
        if (!$isRegulatory && $logisticsService && !empty($logisticsService->service_options)) {
            $options = explode(',', $logisticsService->service_options);
        }

        $shipmentStatus = 'NEW';

        if (Shipment::where('quotation_id', $this->id)->exists()) {
            $shipmentStatus = 'ACCEPTED';
        }

        $response = [
            'id' => $this->id,   
            'reference_number' => $this->reference_number,
            'client' => $this->client->full_name,
            'account_specialist' => $this->accountSpecialist->full_name,
            'status' => $this->status,
            'shipment_status' => $shipmentStatus,
            'created_at' => $this->created_at->format('m/d/Y'),
            'updated_at' => $this->updated_at->format('m/d/Y'),
            'issued_quotation_id' => $this->issuedQuotation ? $this->issuedQuotation->id : null,
            'company' => [
                'name' => $this->company_name,
                'address' => $this->company_address,
                'contact_person' => $this->contact_person,
                'contact_number' => $this->contact_number,
                'email' => $this->email,
                'position' => $this->position,
                'business_type' => $regulatoryService?->business_type,
            ],
            'service' => $isRegulatory ? null : [
                'type' => $logisticsService?->service_type,
                'transport_mode' => $logisticsService?->transport_mode,
                'options' => $options,
            ],
            'commodity' => $isRegulatory ? null : [
                'commodity' => $logisticsService?->commodity,
                'cargo_type' => $logisticsService?->cargo_type,
                'container_size' => $logisticsService?->container_size,
            ],
            'shipment' => $isRegulatory ? null : [
                'origin' => $logisticsService?->origin,
                'destination' => $logisticsService?->destination,
            ],
            'regulatory_service' => $isRegulatory ? [
                'type_of_regulatory_assistance' => !empty($regulatoryService?->type_of_regulatory_assistance)
                    ? explode(',', $regulatoryService->type_of_regulatory_assistance)
                    : [],
                'service_level' => $regulatoryService?->application_type,
                'message' => $regulatoryService?->message,
            ] : null,
            'quotation_file' => $this->files()->where('type', 'PROPOSAL')->exists()
                ? $this->files()->where('type', 'PROPOSAL')->get()->map(function($file) {
                    return [
                        'id' => $file->id,
                        'file_name' => $file->original_file_name,
                        'file_url' => asset(Storage::url($file->file_path)),
                    ];
                })
                : 'No file available.',
            'documents' => $this->files()->where('type', 'REQUESTED')->exists()
                ? $this->files()->where('type', 'REQUESTED')->get()->map(function($file) {
                    return [
                        'id' => $file->id,
                        'file_name' => $file->original_file_name,
                        'file_url' => asset(Storage::url($file->file_path)),
                        'file_type' => $file->file_type
                    ];
                })
                : 'No documents available.',
            'remarks' => $isRegulatory ? null : $logisticsService?->remarks,
            'conversation_id' => $conversationId
        ];

        if ($isWeb) {
            $response['client'] = [
                'full_name' => $this->client->full_name,
                'company_name' => $this->client->company_name,
                'contact_number' => $this->client->contact_number,
                'email' => $this->client->email,
            ];
        }

        return $response;
    }
}
