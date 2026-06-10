<?php

namespace App\Repositories\Quotation;

use App\Models\BusinessType;
use App\Models\ContainerSize;
use App\Models\RegulatoryAssistanceType;
use App\Models\ServiceOption;
use App\Models\ServiceType;
use App\Repositories\BaseRepository;
use App\Models\User;
use App\Models\QuotationFileChecklistItem;

class EnumQuotationOptionsRepository extends BaseRepository
{
    public function execute($request){
        $validated = $request->validated();
        $user = User::find($request->input('client_id'));
        if (auth()->user()->hasRole('Client')) {
            $user = User::find(auth()->id());
        }

        $autofillDetails = [
            'full_name' => $user->full_name,
            'company' => [
                'name' => $user->company?->name ?? null,
                'address' => $user->company?->address->registered_address ?? null,
                'position' => $user->company_position,
                'contact_number' => $user->contact_number,
                'email' => $user->email,
                'business_type' => $user->company ? $user->company->businessType->name : $user->company?->business_type_other ?? null,
            ],
        ];
        $clients = User::role('Client')->pluck('full_name', 'id');
        $businessTypes = [];
        $regulatoryAssistanceTypes = [];
        $serviceTypes = [];
        $transportModes = [];
        $serviceOptions = [];
        $cargoType = [];
        $containerSize = [];
        $documentChecklist = [];

        if (isset($validated['service'])) {
            if ($validated['service'] === 'REGULATORY') {
                $documentChecklist = QuotationFileChecklistItem::whereIn('visibility', ['REGULATORY', 'BOTH'])->pluck('name');
                $serviceTypes = ServiceType::where('service', 'REGULATORY')->pluck('name');
                if (isset($validated['service_type'])) {
                    $serviceOptions = ServiceOption::where('status', 'ENABLED')
                        ->where('service_type_id', null)
                        ->orWhere('service_type_id', ServiceType::where('name', $validated['service_type'])->first()->id)
                        ->pluck('name');
                } else {
                    $serviceOptions = ServiceOption::where('status', 'ENABLED')->pluck('name');
                }
                $businessTypes = BusinessType::pluck('name');
                $regulatoryAssistanceTypes = RegulatoryAssistanceType::pluck('name');
            } elseif ($validated['service'] === 'LOGISTICS') {
                $documentChecklist = QuotationFileChecklistItem::whereIn('visibility', ['LOGISTICS', 'BOTH'])->pluck('name');
                $serviceTypes = ServiceType::where('service', 'LOGISTICS')->pluck('name');
                $transportModes = ['AIR', 'SEA'];
                if (isset($validated['service_type'])) {
                    $serviceOptions = ServiceOption::where('status', 'ENABLED')
                        ->where('service_type_id', null)
                        ->orWhere('service_type_id', ServiceType::where('name', $validated['service_type'])->first()->id)
                        ->pluck('name');
                } else {
                    $serviceOptions = ServiceOption::where('status', 'ENABLED')->pluck('name');
                }
                $cargoType = ['CONTAINERIZED', 'LCL'];
                $containerSize = ContainerSize::pluck('size');
            }
        }

        $quotationOptions = [
            'clients' => $clients,
            'autofill_details' => $autofillDetails,
            'business_types' => $businessTypes,
            'regulatory_assistance_types' => $regulatoryAssistanceTypes,
            'service_types' => $serviceTypes,
            'transport_modes' => $transportModes,
            'service_options' => $serviceOptions,
            'cargo_type' => $cargoType,
            'container_size' => $containerSize,
            'document_checklist' => $documentChecklist,
        ];

        return $this->success('Quotation options fetched', $quotationOptions, 200);
    }
}
