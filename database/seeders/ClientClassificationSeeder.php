<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ClientClassification;

class ClientClassificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classifications = [
            'REGULAR', 
            'VIP',
            'VVIP'
        ];

        foreach ($classifications as $classification) {
            ClientClassification::create(['name' => $classification]);
        }
    }
}
