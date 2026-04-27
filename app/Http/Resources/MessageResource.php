<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // 1. Determine Sender Details
        // If real user exists, use them. If null (System), use "JLTCB" details.
        $senderData = $this->sender ? [
            'id' => $this->sender->id,
            'full_name' => $this->sender->full_name,
            'image_path' => asset($this->sender->image_path), // User's avatar
        ] : [
            'id' => null,
            'full_name' => 'JLTCB', // <--- Static Name
            'image_path' => asset('storage/images/jltcb.png'), // <--- Static Logo Path (Make sure this exists in public/)
        ];

        // 2. Base Structure
        $data = [
            'id' => $this->id,
            'type' => $this->type, 
            'created_at' => $this->created_at, 
            'sender' => $senderData, // Use the logic above
        ];

        // 3. Add Type-Specific Data
        switch ($this->type) {
            case 'QUOTATION_CARD':
                $data['quotation'] = $this->reference ? [
                    'as_full_name' => $this->reference->accountSpecialist->full_name,
                    'id' => $this->reference->id,
                    'reference_number' => $this->reference->reference_number,
                    'commodity' => $this->reference->commodity,
                    'cargo_type' => $this->reference->cargo_type,
                    'volume' => $this->reference->container_size,
                    'date_created' => $this->reference->created_at,
                ] : null;
                break;

            case 'SHIPMENT_CARD':
                $data['shipment'] = $this->reference ? [
                    'as_full_name' => $this->reference->accountSpecialist->full_name,
                    'id' => $this->reference->id,
                    'reference_number' => $this->reference->reference_number,
                    'commodity' => $this->reference->commodity,
                    'cargo_type' => $this->reference->cargo_type,
                    'volume' => $this->reference->container_size,
                    'date_created' => $this->reference->created_at,
                ] : null;
                break;

            case 'TEXT':
                $data['content'] = $this->content;
                break;

            case 'IMAGE':
                $data['content'] = $this->content;
                $data['file_name'] = $this->file_name;
                $data['file_url'] = route('chat.attachments.view', $this->id);

                $absolutePath = Storage::disk('local')->path($this->attachment_path);
                [$width, $height] = getimagesize($absolutePath); 

                $data['width'] = $width;  
                $data['height'] = $height;  
                break;

            case 'FILE':
                $data['content'] = $this->content;
                $data['file_name'] = $this->file_name;
                $data['file_url'] = route('chat.attachments.view', $this->id);
                break;

            default:
                $data['content'] = $this->content;
                break;
        }

        return $data;
    }
}
