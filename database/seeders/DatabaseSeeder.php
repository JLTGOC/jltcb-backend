<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Clear storage/app/public directory
        $storagePath = storage_path('app/public');
        if (File::isDirectory($storagePath)) {
            File::deleteDirectory($storagePath);
            File::makeDirectory($storagePath);
        }

        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            ArticleSeeder::class,
            ReelSeeder::class,
            ServiceOptionSeeder::class,
            QuotationSeeder::class
        ]);
    }
}
