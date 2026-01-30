<?php

namespace App\Services\Dashboard;

class ClientDashboardService
{
    public function getStats($user): array
    {
        return [
            'user' => [
                'full_name' => $user->full_name,
                'company' => $user->company_name,
                'image_path' => $user->image_path,
            ],
            'shipments' => [
                'ongoing_count' => 8,
                'completed_count' => 25,
            ],
            'quotations' => [
                'requested_count' => 7,
                'responded_count' => 4,
            ],
        ];
    }
}