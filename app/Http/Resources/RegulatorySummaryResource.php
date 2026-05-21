<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegulatorySummaryResource extends JsonResource
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
            'application_type' => $this->regulatoryService?->type_of_regulatory_assistance,
            'type_of_application' => $this->regulatoryService?->application_type,
            'issue_date' => $this->issuedQuotations?->created_at,
            'expiry_date' => $this->issuedQuotations?->rate_validity,
            'person_in_charge' => $this->accountSpecialist?->full_name,
            'pic_image_path' => $this->accountSpecialist?->image_path,
            'status' => fake()->randomElement(['For Evaluation', 'Approved', 'Under Review', 'Soon to Expire']) //temporary static status
        ];
    }
}
