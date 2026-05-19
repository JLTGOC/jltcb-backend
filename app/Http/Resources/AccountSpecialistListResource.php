<?php

namespace App\Http\Resources;

use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AccountSpecialistListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'employee_id' => $this->id,
            'profile_image' => asset(Storage::url($this->image_path)),
            'employee_name' => $this->full_name,
            'email' => $this->email,
            'contact_number' => $this->contact_number,
            'request_accepted' => $this->request_accepted_count,
            'quotation_sent' => $this->quotation_sent_count,
            'qt_accepted_by_client' => $this->qt_accepted_by_client_count,
            'role' => $this->getRoleNames()->first(),
            'last_activity' => $this->whenLoaded('latestQuotationAccepted', $this->latestQuotationAccepted?->created_at),
        ];
    }
}
