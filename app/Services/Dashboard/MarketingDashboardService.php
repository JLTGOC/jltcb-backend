<?php

namespace App\Services\Dashboard;

use App\Models\Article;
use App\Models\Reel;
use App\Models\User;

class MarketingDashboardService
{
    public function getStats($user): array
    {
        return [
            'user' => [
                'role' => strtoupper($user->getRoleNames()->first()),
                'company' => $user->company_name,
                'image_path' => $user->image_path,
            ],
            'views_count' => number_format(Reel::sum('view_count')),
            'clients_count' => number_format(User::role('Client')->count()),
            'total_videos' => number_format(Reel::count()),
            'total_articles' => number_format(Article::count()),
        ];
    }
}