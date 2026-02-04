<?php

use App\Models\Reel;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

if (!function_exists('generate_reel_thumbnail')) {
    /**
     * Generate and persist a thumbnail for a reel.
     */
    function generate_reel_thumbnail(Reel $reel, int $second = 1): ?string
    {
        if (!$reel->video_path) {
            return null;
        }

        $disk = Storage::disk('public');
        $sourceDiskPath = Reel::normalizePublicDiskPath($reel->video_path);

        if (!$sourceDiskPath || !$disk->exists($sourceDiskPath)) {
            return null;
        }

        $thumbnailDir = 'reels/thumbnails';
        if (!$disk->exists($thumbnailDir)) {
            $disk->makeDirectory($thumbnailDir);
        }

        $filename = pathinfo($sourceDiskPath, PATHINFO_FILENAME);
        $thumbnailDiskPath = $thumbnailDir . '/' . $filename . '.jpg';

        FFMpeg::fromDisk('public')
            ->open($sourceDiskPath)
            ->getFrameFromSeconds($second)
            ->export()
            ->toDisk('public')
            ->save($thumbnailDiskPath);

        $reel->thumbnail_path = Reel::makePublicPath($thumbnailDiskPath, $reel->video_path);
        $reel->save();

        return $reel->thumbnail_path;
    }
}
