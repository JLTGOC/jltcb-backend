<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuotationTemplateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'service_type' => $this->service_type,
            'is_active' => $this->is_active,
            'quotation_details' => DetailsConfigResource::collection(
                $this->whenLoaded('detailConfigs')
            ),
            'billing_details' => TemplateChargeResource::collection(
                $this->whenLoaded('templateCharges')
            )
        ];
    }
}
