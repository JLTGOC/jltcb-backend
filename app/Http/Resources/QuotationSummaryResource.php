<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class QuotationSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'reference_number' => $this->reference_number,
            'service_type' => $this->regulatoryService ? 'REGULATORY' : 'LOGISTICS',
            'date_quoted' => $this->issuedQuotations?->created_at,
            'valid_until' => $this->issuedQuotations?->rate_validity,
            'quoted_by' => $this->issuedQuotations?->issuedBy?->full_name,
            'pic_image_path' => asset(Storage::url($this->creator?->image_path)),
            'status' => $this->status === 'RESPONDED' ? 'PENDING' : $this->status,
            'alerts' => $this->issuedQuotations?->expirationStatus,
            'days_until_expiration' => $this->issuedQuotations?->daysUntilExpiration
        ];
    }
}
