<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Industry;

class IndustrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $industries = [
            'AGRICULTURE',
            'ENERGY AND POWER',
            'CHEMICALS',
        ];

        foreach ($industries as $industry) {
            Industry::create(['name' => $industry]);
        }
    }
}
