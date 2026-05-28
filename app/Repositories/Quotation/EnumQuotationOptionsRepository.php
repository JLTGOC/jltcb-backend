<?php

namespace App\Repositories\Quotation;

use App\Models\BusinessType;
use App\Models\ContainerSize;
use App\Models\RegulatoryAssistanceType;
use App\Models\ServiceOption;
use App\Models\ServiceType;
use App\Repositories\BaseRepository;

class EnumQuotationOptionsRepository extends BaseRepository
{
    public function execute($request){
        $validated = $request->validated();
        $user = auth()->user();

        $autofillDetails = [
            'full_name' => $user->full_name,
            'company' => [
                'name' => $user->company_name,
                'address' => $user->company_address,
                'position' => $user->company_position,
                'contact_number' => $user->contact_number,
                'email' => $user->email,
                'business_type' => $user->business_type,
            ],
        ];
        $businessTypes = [];
        $regulatoryAssistanceTypes = [];
        $serviceTypes = [];
        $transportModes = [];
        $serviceOptions = [];
        $cargoType = [];
        $containerSize = [];

        if (isset($validated['service'])) {
            if ($validated['service'] === 'REGULATORY') {
                $serviceTypes = ServiceType::where('service', 'REGULATORY')->pluck('name');
                $businessTypes = BusinessType::pluck('name');
                $regulatoryAssistanceTypes = RegulatoryAssistanceType::pluck('name');
            } elseif ($validated['service'] === 'LOGISTICS') {
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
            'autofill_details' => $autofillDetails,
            'business_types' => $businessTypes,
            'regulatory_assistance_types' => $regulatoryAssistanceTypes,
            'service_types' => $serviceTypes,
            'transport_modes' => $transportModes,
            'service_options' => $serviceOptions,
            'cargo_type' => $cargoType,
            'container_size' => $containerSize,
        ];

        return $this->success('Quotation options fetched', $quotationOptions, 200);
    }
}
