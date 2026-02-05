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
                'ongoing_count' => $user->shipments()->where('status', 'ONGOING')->count(),
                'completed_count' => $user->shipments()->where('status', 'DELIVERED')->count(),
            ],
            'quotations' => [
                'requested_count' => $user->quotations()->where('status', 'REQUESTED')->count(),
                'responded_count' => $user->quotations()->where('status', 'RESPONDED')->count(),
            ],
        ];
    }
}