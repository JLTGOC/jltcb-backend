<?php

namespace App\Repositories\Quotation;

use App\Models\BusinessType;
use App\Models\ContainerSize;
use App\Models\RegulatoryAssistanceType;
use App\Models\ServiceOption;
use App\Repositories\BaseRepository;

class EnumQuotationOptionsRepository extends BaseRepository
{
    public function execute(){
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
        $businessTypes = BusinessType::pluck('name');
        $regulatoryAssistanceTypes = RegulatoryAssistanceType::pluck('name');
        $serviceTypes = ['IMPORT', 'EXPORT'];
        $transportModes = ['AIR', 'SEA'];
        $serviceOptions = ServiceOption::where('status', 'ENABLED')->pluck('name');
        $cargoType = ['CONTAINERIZED', 'LCL'];
        $containerSize = ContainerSize::pluck('size');

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
