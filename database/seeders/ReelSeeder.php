<?php

namespace Database\Seeders;

use App\Models\Reel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reels = [
            [
                'video_path' => 'seeders/reels/DID YOU KNOW - CHESCA.mp4',
                'view_count' => 1250,
            ],
            [
                'video_path' => 'seeders/reels/ONE WRONG CUSTOMSCODE - CHESCA.mov',
                'view_count' => 2340,
            ],
            [
                'video_path' => 'seeders/reels/DID YOU KNOW - CHESCA.mp4',
                'view_count' => 1890,
            ],
            [
                'video_path' => 'seeders/reels/ONE WRONG CUSTOMSCODE - CHESCA.mov',
                'view_count' => 3100,
            ],
            [
                'video_path' => 'seeders/reels/DID YOU KNOW - CHESCA.mp4',
                'view_count' => 2750,
            ],
            [
                'video_path' => 'seeders/reels/ONE WRONG CUSTOMSCODE - CHESCA.mov',
                'view_count' => 1980,
            ],
            [
                'video_path' => 'seeders/reels/DID YOU KNOW - CHESCA.mp4',
                'view_count' => 2200,
            ],
            [
                'video_path' => 'seeders/reels/ONE WRONG CUSTOMSCODE - CHESCA.mov',
                'view_count' => 1450,
            ],
        ];

        foreach ($reels as $reel) {
            Reel::create($reel);
        }
    }
}
