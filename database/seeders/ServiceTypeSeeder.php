<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ServiceType;

class ServiceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $serviceTypes = [
            ['name' => 'IMPORT', 'service' => 'LOGISTICS', 'code' => 'IM', 'status' => 'ENABLED'],
            ['name' => 'EXPORT', 'service' => 'LOGISTICS', 'code' => 'EX', 'status' => 'ENABLED'],
            ['name' => 'PERMITS & LICENSING', 'service' => 'REGULATORY', 'code' => 'PL', 'status' => 'ENABLED'],
            ['name' => 'POST CLEARANCE AUDIT', 'service' => 'REGULATORY', 'code' => 'PCA', 'status' => 'ENABLED'],
            ['name' => 'ACCREDITATION', 'service' => 'REGULATORY', 'code' => 'ACC', 'status' => 'ENABLED'],
        ];

        foreach ($serviceTypes as $serviceType) {
            ServiceType::create($serviceType);
        }
    }
}
