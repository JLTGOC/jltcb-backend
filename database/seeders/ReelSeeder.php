<?php

namespace Database\Seeders;

use App\Models\Reel;
use Database\Seeders\Traits\SeederFileTrait;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReelSeeder extends Seeder
{
    use SeederFileTrait;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clean up old files from public storage
        $this->cleanupSeederFiles('reels');

        $videos = [
            'DID YOU KNOW - CHESCA.mp4',
            'ONE WRONG CUSTOMSCODE - CHESCA.mov',
        ];

        $reels = [
            [
                'video_path' => $this->copySeederFile('reels', $videos[0]),
                'view_count' => 1250,
            ],
            [
                'video_path' => $this->copySeederFile('reels', $videos[1]),
                'view_count' => 2340,
            ],
            [
                'video_path' => $this->copySeederFile('reels', $videos[0]),
                'view_count' => 1890,
            ],
            [
                'video_path' => $this->copySeederFile('reels', $videos[1]),
                'view_count' => 3100,
            ],
            [
                'video_path' => $this->copySeederFile('reels', $videos[0]),
                'view_count' => 2750,
            ],
            [
                'video_path' => $this->copySeederFile('reels', $videos[1]),
                'view_count' => 1980,
            ],
            [
                'video_path' => $this->copySeederFile('reels', $videos[0]),
                'view_count' => 2200,
            ],
            [
                'video_path' => $this->copySeederFile('reels', $videos[1]),
                'view_count' => 1450,
            ],
        ];

        foreach ($reels as $reel) {
            Reel::create($reel);
        }
    }
}
