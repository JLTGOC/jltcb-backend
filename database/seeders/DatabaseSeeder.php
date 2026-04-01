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
        // Clear storage/app/public directory (preserve .gitignore)
        $storagePath = storage_path('app/public');
        if (File::isDirectory($storagePath)) {
            $files = File::files($storagePath);
            $directories = File::directories($storagePath);
            
            // Delete all files except .gitignore
            foreach ($files as $file) {
                if (basename($file) !== '.gitignore') {
                    File::delete($file);
                }
            }
            
            // Delete all directories
            foreach ($directories as $directory) {
                File::deleteDirectory($directory);
            }
        } else {
            File::makeDirectory($storagePath, 0755, true);
        }

        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            ArticleSeeder::class,
            ReelSeeder::class,
            ServiceOptionSeeder::class,
            QuotationSeeder::class,
            QuotationFileSeeder::class,
            ChatSeeder::class,
            ConfigurationSeeder::class,
            TemplateSeeder::class,
            QuotationFieldSeeder::class,
        ]);
    }
}
