<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\QuotationFile;
use Illuminate\Support\Facades\Storage;

class JobOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $options = $this->quotation->logisticsService?->service_options
            ? explode(',', $this->quotation->logisticsService->service_options)
            : [];

        $quotationProposal = QuotationFile::where('quotation_id', $this->quotation_id)
            ->where('type', 'PROPOSAL')
            ->first();

        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'quotation_id' => $this->quotation->id,
            'job_type' => $this->job_type,
            'as_id' => $this->as_id,
            'operations_id' => $this->operations_id,
            'finance_id' => $this->finance_id,
            'subject' => $this->subject,
            'email_body' => $this->email_body,
            'client' => [
                'consignee' => $this->quotation->company_name,
                'shipper' => $this->quotation->client->full_name,
                'client_type' => $this->client_type,
                'accredited' => $this->accredited,
                'remarks' => $this->client_remarks,
            ],
            'service' => [
                'service_level' => $this->service_level,
                'bl_no' => $this->bl_no,
                'eta' => $this->eta,
                'etd' => $this->etd,
            ],
            'shipment' => [
                'hs_code' => $this->hs_code ?? null,
                'rod' => $this->rod ?? null,
                'permits' => $this->permits ?? null,
                'special_remarks' => $this->special_remarks ?? null,
            ],
            'target' => [
                'target_delivery_date' => $this->target_delivery_date ?? null,
                'target_completion_date' => $this->target_completion_date ?? null,
                'special_remarks' => $this->commitment_remarks ?? null,
            ],
            'billing_details' => [
                'terms_of_payment' => $this->billingDetails->terms_of_payment ?? null,
                'billing_date' => $this->billingDetails->billing_date ?? null,
                'shall_be_billed' => $this->billingDetails->shall_be_billed ?? null,
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'quotation_file' => $quotationProposal ? [
                'id' => $quotationProposal->id,
                'file_name' => $quotationProposal->original_file_name,
                'file_url' => asset(Storage::url($quotationProposal->file_path)),
                'file_type' => $quotationProposal->file_type
            ] : null,
            'documents' => $this->quotation->files()->where('type', 'REQUESTED')->exists()
                ? $this->quotation->files()->where('type', 'REQUESTED')->get()->map(function($file) {
                    return [
                        'id' => $file->id,
                        'file_name' => $file->original_file_name,
                        'file_url' => asset(Storage::url($file->file_path)),
                        'file_type' => $file->file_type
                    ];
                })
                : 'No documents available.',
        ];
    }
}
