<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ServiceOption;

class ServiceOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = [
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

        ServiceOption::create([
            'name' => 'ALL IN',
            'status' => 'ENABLED',
            'service_type_id' => null
        ]);

        foreach ($names as $name) {
            ServiceOption::create([
                'name' => $name,
                'status' => 'ENABLED',
                'service_type_id' => 1
            ]);

            ServiceOption::create([
                'name' => $name,
                'status' => 'ENABLED',
                'service_type_id' => 2
            ]);
        }
    }
}
