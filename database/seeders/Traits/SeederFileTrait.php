<?php

namespace Database\Seeders\Traits;

use Illuminate\Support\Facades\File;

trait SeederFileTrait
{
    /**
     * Copy a file from seeders directory to public storage
     * 
     * @param string $sourceDir - Subdirectory in database/seeders (e.g., 'reels', 'articles', 'images')
     * @param string $filename - Name of the file
     * @return string - The path to use in the database
     */
    protected function copySeederFile(string $sourceDir, string $filename): string
    {
        $sourcePath = database_path("seeders/{$sourceDir}/{$filename}");
        $publicStorageDir = storage_path("app/public/{$sourceDir}");
        $destinationPath = "{$publicStorageDir}/{$filename}";
        
        // Create the public storage directory if it doesn't exist
        if (!File::exists($publicStorageDir)) {
            File::makeDirectory($publicStorageDir, 0755, true);
        }
        
        // Copy the file if source exists
        if (File::exists($sourcePath)) {
            File::copy($sourcePath, $destinationPath);
        }
        
        // Return the path to use in the database
        return "storage/{$sourceDir}/{$filename}";
    }

    /**
     * Clean up files from public storage before seeding
     * 
     * @param string $sourceDir - Subdirectory to clean (e.g., 'reels', 'articles', 'images')
     */
    protected function cleanupSeederFiles(string $sourceDir): void
    {
        $publicStorageDir = storage_path("app/public/{$sourceDir}");
        
        if (File::exists($publicStorageDir)) {
            File::deleteDirectory($publicStorageDir);
        }
    }
}
