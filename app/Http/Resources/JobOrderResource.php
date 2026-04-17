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
                'client_type' => $this->jobOrderClient->client_type,
                'accredited' => $this->jobOrderClient->accredited,
                'remarks' => $this->jobOrderClient->client_remarks,
            ],
            'service' => $this->jobOrderShipment ? [
                'service_level' => $this->jobOrderShipment->service_level,
                'bl_no' => $this->jobOrderShipment->bl_no,
                'eta' => $this->jobOrderShipment->eta,
                'etd' => $this->jobOrderShipment->etd,
            ] : null,
            'shipment' => $this->jobOrderShipment ? [
                'commodity' => $this->quotation->logisticsService->commodity ?? null,
                'cargo_type' => $this->quotation->logisticsService->cargo_type ?? null,
                'container_size' => $this->quotation->logisticsService->container_size ?? null,
                'hs_code' => $this->jobOrderShipment->hs_code ?? null,
                'rod' => $this->jobOrderShipment->rod ?? null,
                'permits' => $this->jobOrderShipment->permits ?? null,
                'if_coordinated' => $this->jobOrderShipment->if_coordinated ?? null,
                'special_remarks' => $this->jobOrderShipment->shipment_remarks ?? null,
            ] : null,
            'target' => $this->jobOrderShipment ? [
                'target_delivery_date' => $this->jobOrderShipment->target_delivery_date ?? null,
                'target_completion_date' => $this->jobOrderShipment->target_completion_date ?? null,
                'special_remarks' => $this->jobOrderShipment->commitment_remarks ?? null,
            ] : null,
            'billing_details' => $this->jobOrderBilling ? [
                'terms_of_payment' => $this->jobOrderBilling->terms_of_payment ?? null,
                'billing_date' => $this->jobOrderBilling->billing_date ?? null,
                'shall_be_billed' => $this->jobOrderBilling->shall_be_billed ?? null,
            ] : null,
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
