<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TransactionType;

class TransactionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            'COORDINATED',
            'STRAIGHT',
        ];

        foreach ($types as $type) {
            TransactionType::create(['name' => $type]);
        }
    }
}
