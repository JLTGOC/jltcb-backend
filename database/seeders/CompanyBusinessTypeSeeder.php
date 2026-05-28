<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CompanyBusinessType;

class CompanyBusinessTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businessTypes = ['COOPERATIVE', 'CORPORATION', 'E-COMMERCE', 'INDIVIDUAL IMPORTER', 'GOVERNMENT AGENCY', 'IMPORT-EXPORT AGENT', 'MULTINATIONAL COMPANY', 'NON-PROFIT ORGANIZATION', 'PARTNERSHIP', 'PEZA-REGISTERED ENTERPRISE', 'SOLE PROPRIETORSHIP'];

        foreach ($businessTypes as $type) {
            CompanyBusinessType::create(['name' => $type]);
        }
    }
}
