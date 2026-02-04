<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reel extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_path',
        'thumbnail_path',
        'view_count',
    ];

    protected $casts = [
        'view_count' => 'integer',
    ];

    /**
     * Generate and persist a thumbnail for this reel.
     */
    public function generateThumbnail(int $second = 1): ?string
    {
        return generate_reel_thumbnail($this, $second);
    }

    /**
     * Convert a stored public path to a disk path.
     */
    public static function normalizePublicDiskPath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $normalized = ltrim($path, '/');

        return Str::startsWith($normalized, 'storage/')
            ? Str::after($normalized, 'storage/')
            : $normalized;
    }

    /**
     * Build a public path matching the video's style.
     */
    public static function makePublicPath(string $diskPath, string $videoPath): string
    {
        $diskPath = ltrim($diskPath, '/');

        return Str::startsWith(ltrim($videoPath, '/'), 'storage/')
            ? 'storage/' . $diskPath
            : $diskPath;
    }

    /**
     * Increment the view count for this reel.
     */
    public function incrementViewCount()
    {
        $this->increment('view_count');
    }
}
