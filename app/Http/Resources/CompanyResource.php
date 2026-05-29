<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($request->routeIs('companies.show')) {
            if ($request->basic_info) {
                $array = parent::toArray($request);
                $industries = $this->companyIndustries->map(function ($companyIndustry) {
                    return $companyIndustry->industry ? $companyIndustry->industry->name : null;
                })->filter()->values();
                $array['account_handler'] = $this->accountHandler ? [
                    'id' => $this->accountHandler->id,
                    'full_name' => $this->accountHandler->full_name,
                    'username' => $this->accountHandler->username,
                    'role' => $this->accountHandler->roles()->first()->name ?? null,
                    'image_path' => asset($this->accountHandler->image_path),
                ] : null;
                $array['transaction_type'] = $this->transactionType ? $this->transactionType->name : null;
                $array['client_classification'] = $this->clientClassification ? $this->clientClassification->name : null;
                $array['company_type'] = $this->companyType ? $this->companyType->name : null;
                $array['business_type'] = $this->businessType ? $this->businessType->name : null;
                $array['industry'] = $industries;
                $array['activation_date'] = $this->activation_date ? Carbon::parse($this->activation_date)->format('M d,Y') : null;
                return $array;
            }
            if ($request->address) {
                return [
                    'registered_address' => $this->address->registered_address ?? null,
                    'office_address' => $this->address->office_address ?? null,
                    'usual_port' => $this->address->usual_port ?? null,
                    'origin_country' => $this->address->origin_country ?? null,
                    'destination_country' => $this->address->destination_country ?? null,
                    'warehouse_addresses' => $this->warehouseAddresses ?? [],
                    'delivery_addresses' => $this->deliveryAddresses ?? [],
                ];
            }
            if ($request->contacts) {
                $primaryContact = $this->contacts()->where('type', 'PRIMARY')->first();
                $secondaryContact = $this->contacts()->where('type', 'SECONDARY')->first();
                $billingContact = $this->contacts()->where('type', 'BILLING')->first();

                return [
                    'primary_contact' => $primaryContact ? [
                        'full_name' => $primaryContact->full_name,
                        'position' => $primaryContact->position,
                        'email' => $primaryContact->email,
                        'contact_number' => $primaryContact->contact_number,
                    ] : null,
                    'secondary_contact' => $secondaryContact ? [
                        'full_name' => $secondaryContact->full_name,
                        'position' => $secondaryContact->position,
                        'email' => $secondaryContact->email,
                        'contact_number' => $secondaryContact->contact_number,
                    ] : null,
                    'billing_contact' => $billingContact ? [
                        'full_name' => $billingContact->full_name,
                        'position' => $billingContact->position,
                        'email' => $billingContact->email,
                        'contact_number' => $billingContact->contact_number,
                    ] : null,
                ];
            }
            if ($request->registration) {
                return [
                    'tin' => $this->registration->tin ?? null,
                    'bir_registration_number' => $this->registration->bir_registration_number ?? null,
                    'cprs_status' => $this->registration->cprs_status ?? null,
                    'importer_accreditation_number' => $this->registration->importer_accreditation_number ?? null,
                    'importer_accreditation_expiry' => $this->registration->importer_accreditation_expiry ? Carbon::parse($this->registration->importer_accreditation_expiry)->format('m/d/Y') : null,
                    'exporter_accreditation_number' => $this->registration->exporter_accreditation_number ?? null,
                    'exporter_accreditation_expiry' => $this->registration->exporter_accreditation_expiry ? Carbon::parse($this->registration->exporter_accreditation_expiry)->format('m/d/Y') : null,
                    'special_permits' => $this->registration->special_permits ?? null,
                    'compliance_risk' => $this->registration->compliance_risk ?? null,
                    'representatives' => $this->representatives->map(function ($representative) {
                        return [
                            'full_name' => $representative->full_name,
                        ];
                    }),
                ];
            }
            if ($request->pricing) {
                return [$this->pricing ?? null];
            }
            if ($request->monitoring) {
                return [$this->monitoring ?? null];
            }
            if ($request->operation) {
                return [$this->operation ?? null];
            }
            if ($request->insights) {
                return [$this->insight ?? null];
            }
            if ($request->documents) {
                return $this->documents->map(function ($document) {
                    return [
                        'id' => $document->id,
                        'file_name' => $document->file_name,
                        'file_url' => URL::temporarySignedRoute(
                            'files.view', 
                            Carbon::now()->addMinutes(10), 
                            [
                                'file' => $document->id
                            ]),
                        'file_type' => $document->file_type,
                        'created_at' => $document->created_at,
                        'updated_at' => $document->updated_at,
                    ];
                })->toArray();
            }
        }

        return [
            'id' => $this->id,
            'company_id' => 'C' . str_pad($this->id, 4, '0', STR_PAD_LEFT),
            'name' => $this->name,
            'clasification' => $this->clientClassification ? $this->clientClassification->name : null,
            'consignee' => $this->consignee_used,
            'account_handler' => $this->accountHandler 
                ? [
                    'id' => $this->accountHandler->id,
                    'full_name' => $this->accountHandler->full_name,
                    'username' => $this->accountHandler->username,
                    'role' => $this->accountHandler->roles()->first()->name ?? null,
                    'image_path' => asset($this->accountHandler->image_path),
                ] : null,
        ];
    }
}
