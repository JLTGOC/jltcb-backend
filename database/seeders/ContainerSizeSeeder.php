<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ContainerSize;

class ContainerSizeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sizes = ['1x20', '1x40'];

        foreach ($sizes as $size) {
            ContainerSize::create(['size' => $size]);
        }
    }
}
