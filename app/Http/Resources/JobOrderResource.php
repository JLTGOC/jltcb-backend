<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobOrderResource extends JsonResource
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
            'reference_number' => $this->reference_number,
            'job_type' => $this->job_type,
            'as_id' => $this->as_id,
            'operations_id' => $this->operations_id,
            'finance_id' => $this->finance_id,
            'quotation_id' => $this->quotation_id,
            'subject' => $this->subject,
            'email_body' => $this->email_body,
            'client' => [
                'consignee' => $this->quotation->company_name,
                'shipper' => $this->quotation->client->full_name,
                'client_type' => $this->client_type,
                'accredited' => $this->accredited,
                'tone_and_attitude' => $this->tone_and_attitude,
                'remarks' => $this->remarks,
            ],
            'service' => [
                'service_level' => $this->service_level,
                'bl_no' => $this->bl_no,
                'eta' => $this->eta,
                'etd' => $this->etd,
            ],
            'shipment' => [
                'hs_code' => $this->hs_code ?? null,
                'permits' => $this->permits ?? null,
                'special_remarks' => $this->special_remarks ?? null,
            ],
            'billing_details' => [
                'terms_of_payment' => $this->billingDetails->terms_of_payment ?? null,
                'billing_date' => $this->billingDetails->billing_date ?? null,
                'shall_be_billed' => $this->billingDetails->shall_be_billed ?? null,
                'closing_remarks' => $this->billingDetails->closing_remarks ?? null,
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
