<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BillingMode;

class BillingModeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $billingModes = [
            'AS PER QUOTE',
            'AS PER RECEIPT',
            'THIRD-PARTY RECEIPTED CHARGES ADVANCES, DEBIT NOTE, CHARGES UPON DELIVERY',
            'CARGO CONSOLIDATION (CC), DIRECT EXPORT (DE)',
            'UPON SERVICE RENDERED (COD)'
        ];

        foreach ($billingModes as $mode) {
            BillingMode::create(['name' => $mode]);
        }
    }
}
