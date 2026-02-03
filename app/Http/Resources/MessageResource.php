<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'created_at' => $this->created_at->format('m/d/Y'), 
            'sender' => $senderData, // Use the logic above
        ];

        // 3. Add Type-Specific Data
        return array_merge($data, match ($this->type) {
            
            'QUOTATION_CARD' => [
                'quotation' => $this->reference ? [
                    'id' => $this->reference->id,
                    'reference_number' => $this->reference->reference_number, // Ensure this matches your DB column
                    'commodity' => $this->reference->commodity,
                    'volume' => $this->reference->volume,
                    'date_created' => $this->reference->created_at->format('m/d/Y'),
                ] : null,
            ],

            'TEXT' => [
                'content' => $this->content,
            ],

            'IMAGE' => [
                'content' => $this->content,
                'attachment_url' => asset($this->attachment_path),
            ],

            'FILE' => [
                'content' => $this->content,
                'file_name' => basename($this->attachment_path),
                'download_url' => asset($this->attachment_path),
            ],

            default => [
                'content' => $this->content,
            ],
        });
    }
}
