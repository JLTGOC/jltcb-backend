<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CompanyType;

class CompanyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            'IMPORTER',
            'EXPORTER',
            'TRADER',
        ];

        foreach ($types as $type) {
            CompanyType::create(['name' => $type]);
        }
    }
}
