<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($request->routeIs('companies.index')) {
            return [
                'id' => 'C' . str_pad($this->id, 4, '0', STR_PAD_LEFT),
                'name' => $this->name,
                'clasification' => $this->clientClassification ? $this->clientClassification->name : null,
                'consignee' => $this->consignee_used,
                'account_handler' => $this->accountHandler 
                    ? [
                        'id' => $this->accountHandler->id,
                        'full_name' => $this->accountHandler->full_name,
                        'username' => $this->accountHandler->username,
                        'role' => $this->accountHandler->roles()->first()->name ?? null,
                        'image_path' => $this->accountHandler->image_path,
                    ] : null,
            ];
        }

        elseif ($request->routeIs('companies.show')) {
            if ($request->basic_info) {
                $array = parent::toArray($request);
                $industries = $this->companyIndustries->map(function ($companyIndustry) {
                    return $companyIndustry->industry ? [
                        'name' => $companyIndustry->industry->name,
                    ] : null;
                })->filter()->values();
                $array['industry'] = $industries;
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
                //
            }
            if ($request->pricing) {
                //
            }
            if ($request->monitoring) {
                //
            }
            if ($request->operation) {
                //
            }
            if ($request->insights) {
                //
            }
            if ($request->documents) {
                //
            }
        }
    }
}
