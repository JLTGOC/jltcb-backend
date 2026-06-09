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
            ['name' => 'IMPORT', 'service' => 'LOGISTICS', 'code' => 'IM'],
            ['name' => 'EXPORT', 'service' => 'LOGISTICS', 'code' => 'EX'],
            ['name' => 'PERMITS & LICENSING', 'service' => 'REGULATORY', 'code' => 'PL'],
            ['name' => 'PRODUCT CLASSIFICATION ASSESSMENT', 'service' => 'REGULATORY', 'code' => 'PCA'],
        ];

        foreach ($serviceTypes as $serviceType) {
            ServiceType::create($serviceType);
        }
    }
}
