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
            ['name' => 'IMPORT', 'service' => 'LOGISTICS'],
            ['name' => 'EXPORT', 'service' => 'LOGISTICS'],
            ['name' => 'BUSINESS SOLUTION', 'service' => 'REGULATORY'],
        ];

        foreach ($serviceTypes as $serviceType) {
            ServiceType::create($serviceType);
        }
    }
}
