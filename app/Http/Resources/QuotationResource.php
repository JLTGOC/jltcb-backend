<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Models\{
    Message,
    Conversation,
    Shipment,
    IssuedQuotation
};
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

class QuotationResource extends JsonResource
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
        $logisticsService = $this->logisticsService;
        $regulatoryService = $this->regulatoryService;
        $issuedQuotation = IssuedQuotation::where('quotation_id', $this->id)->value('id');

        // Prefer explicit regulatory relation when present; otherwise default to logistics shape.
        $isRegulatory = !is_null($regulatoryService);

        $message = Message::where('reference_id', $this->id)->first();
        
        if (!$message) {
            $conversationId = null;
        } else {
            $conversationId = Conversation::where('id', $message->conversation_id)->value('id');
        }

        if ($user->id !== $this->as_id && $user->id !== $this->client_id) {
            $conversationId = null;
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
            'client_id' => $this->client_id,
            'client' => $this->client->full_name,
            'account_specialist' => $this->accountSpecialist->full_name ?? null,
            'status' => $this->status,
            'shipment_status' => $shipmentStatus,
            'created_at' => $this->created_at->format('m/d/Y'),
            'updated_at' => $this->updated_at->format('m/d/Y'),
            'issued_quotation_id' => $issuedQuotation,
            'company' => [
                'name' => $this->company_name,
                'address' => $this->company_address,
                'contact_person' => $this->contact_person,
                'contact_number' => $this->contact_number,
                'email' => $this->email,
                'position' => $regulatoryService?->position ?? $this->client->company_position,
                'business_type' => $regulatoryService?->business_type ?? $this->client->business_type,
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
                        'file_url' => URL::temporarySignedRoute(
                            'files.view', 
                            Carbon::now()->addMinutes(10), 
                            [
                                'file' => $file->id
                            ]),
                        'file_type' => $file->file_type,
                        'created_at' => $file->created_at,
                        'updated_at' => $file->updated_at,
                    ];
                })
                : 'No file available.',
            'documents' => $this->files()->where('type', 'REQUESTED')->exists()
                ? $this->files()->where('type', 'REQUESTED')->get()->map(function($file) {
                    return [
                        'id' => $file->id,
                        'file_name' => $file->original_file_name,
                        'file_url' => URL::temporarySignedRoute(
                            'files.view', 
                            Carbon::now()->addMinutes(10), 
                            [
                                'file' => $file->id
                            ]),
                        'file_type' => $file->file_type,
                        'created_at' => $file->created_at,
                        'updated_at' => $file->updated_at,
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
