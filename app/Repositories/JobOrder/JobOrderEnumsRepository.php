<?php

namespace App\Repositories\JobOrder;

use App\Models\BillingMode;
use App\Models\Quotation;
use App\Repositories\BaseRepository;
use App\Models\ServiceLevel;

class JobOrderEnumsRepository extends BaseRepository
{
    public function execute($request){
        $autofillDetails = null;
        
        if ($request->has('quotation_reference_number')) {
            $quotation = Quotation::where('reference_number', $request->quotation_reference_number)->first() ?? null;
            $client = $quotation->client ?? null;
            $autofillDetails = [
                'company_name' => $client->company?->name ?? null,
                'full_name' => $client->full_name ?? null,
                'commodity' => $quotation->logisticsService?->commodity ?? null,
                'cargo_type' => $quotation->logisticsService?->cargo_type ?? null,
                'container_size' => $quotation->logisticsService?->container_size ?? null,
            ];
        }
            
        $clientTypes = ['NEW', 'RENEWAL'];
        $jobTypes = ['LOGISTICS', 'REGULATORY'];
        $accredited = ['REGULAR', 'EXPEDITED'];
        $serviceLevels = ServiceLevel::pluck('name');
        $shallBeBilled = BillingMode::pluck('name');

        return $this->success('Job Order Enums fetched successfully', [
            'autofill_details' => $autofillDetails,
            'job_types' => $jobTypes,
            'client_types' => $clientTypes,
            'accredited' => $accredited,
            'service_levels' => $serviceLevels,
            'shall_be_billed' => $shallBeBilled,
        ]);
    }
}
