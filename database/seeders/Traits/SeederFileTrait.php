<?php

namespace Database\Seeders\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

trait SeederFileTrait
{
    /**
     * Copy a file from seeders directory to public storage
     * 
     * @param string $sourceDir - Subdirectory in database/seeders (e.g., 'reels', 'articles', 'images')
     * @param string $filename - Name of the file
     * @param string|null $destinationDir - Subdirectory in storage/app/public (defaults to $sourceDir)
     * @return string - The path to use in the database
     */
    protected function copySeederFile(string $sourceDir, string $filename, ?string $destinationDir = null, string $disk = 'public'): string
    {
        $sourcePath = database_path("seeders/{$sourceDir}/{$filename}");

        if (!File::exists($sourcePath)) {
            throw new \RuntimeException("Seeder file not found: {$sourcePath}");
        }

        $destinationDir = $destinationDir ?: $sourceDir;
        $destinationPath = "{$destinationDir}/{$filename}";

        Storage::disk($disk)->put( 
            $destinationPath,
            File::get($sourcePath)
        );

        return $destinationPath;
    }

    /**
     * Clean up files from public storage before seeding
     * 
     */
    protected function cleanupSeederFiles(
        string $directory,
        string $disk = 'public'
    ): void {
        Storage::disk($disk)->deleteDirectory($directory);
    }
}
