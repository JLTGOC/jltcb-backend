<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ServiceLevel;

class ServiceLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $serviceLevels = [
            'CARGO CONSOLIDATION (CC)',
            'DIRECT EXPORT (DE)',
            'INTERNATIONAL FREIGHT FORWARDING (IFF)',
            'CARGO CONSOLIDATION (CC), DIRECT EXPORT (DE)',
            'INTERNATIONAL FREIGHT FORWARDING (IFF), CARGO CONSOLIDATION (CC)',
            'INTERNATIONAL FREIGHT FORWARDING (IFF), CARGO CONSOLIDATION (CC), DIRECT EXPORT (DE)',
        ];

        foreach ($serviceLevels as $level) {
            ServiceLevel::create(['name' => $level]);
        }
    }
}
