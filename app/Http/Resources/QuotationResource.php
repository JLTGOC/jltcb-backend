<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Models\{
    Message,
    Conversation
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
        $message = Message::where('reference_id', $this->id)->first();
        
        if (!$message) {
            $conversationId = null;
        } else {
            $conversationId = Conversation::find($message->conversation_id)->value('id');
        }
        
        $options = explode(',', $this->service_options);

        return [
            'reference_number' => $this->reference_number,
            'client' => $this->client->full_name,
            'account_specialist' => $this->accountSpecialist->full_name,
            'status' => $this->status,
            'created_at' => $this->created_at->format('m/d/Y'),
            'updated_at' => $this->updated_at->format('m/d/Y'),
            'company' => [
                'name' => $this->company_name,
                'address' => $this->company_address,
                'contact_person' => $this->contact_person,
                'contact_number' => $this->contact_number,
                'email' => $this->email,
            ],
            'service' => [
                'type' => $this->service_type,
                'transport_mode' => $this->transport_mode,
                'options' => $options,
            ],
            'commodity' => [
                'commodity' => $this->commodity,
                'cargo_type' => $this->cargo_type,
                'container_size' => $this->container_size ?? null
            ],
            'shipment' => [
                'origin' => $this->origin,
                'destination' => $this->destination,
            ],
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
                    ];
                })
                : 'No documents available.',
            'remarks' => $this->remarks,
            'conversation_id' => $conversationId
        ];
    }
}
