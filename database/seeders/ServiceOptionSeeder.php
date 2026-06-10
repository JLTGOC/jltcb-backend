<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ServiceOption;
use App\Models\ServiceType;

class ServiceOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ServiceOption::create([
            'name' => 'ALL IN',
            'status' => 'ENABLED',
            'service_type_id' => null
        ]);

        $logisticsServiceTypes = ServiceType::where('service', 'LOGISTICS')->pluck('id')->toArray();

        $logisticsServiceOptions = [
            'CUSTOMS CLEARANCE',
            'PEZA PROCESSING & COMPLIANCE',
            'CUSTOMS DISPUTE RESOLUTION',
            'POST CLEARANCE SERVICE',
            'SPECIALIZED ENTRY TYPES',
            'CUSTOMS AND TRADE CONSULTANCY',
            'INTERNATIONAL FREIGHT FORWARDING',
            'DOMESTIC FREIGHT FORWARDING',
            'TRUCKING SERVICES',
            'PROJECT CARGO'
        ];

        foreach ($logisticsServiceOptions as $name) {
            foreach ($logisticsServiceTypes as $serviceTypeId) {
                ServiceOption::create([
                    'name' => $name,
                    'status' => 'ENABLED',
                    'service_type_id' => $serviceTypeId
                ]);
            }
        }

        $regulatoryServiceTypes = ServiceType::where('service', 'REGULATORY')->pluck('id')->toArray();

        $regulatoryServiceOptions = [
            'NEW APPLICATION',
            'RENEWAL',
        ];

        foreach ($regulatoryServiceOptions as $name) {
            foreach ($regulatoryServiceTypes as $serviceTypeId) {
                ServiceOption::create([
                    'name' => $name,
                    'status' => 'ENABLED',
                    'service_type_id' => $serviceTypeId
                ]);
            }
        }
    }
}
