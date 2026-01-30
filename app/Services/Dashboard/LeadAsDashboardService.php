<?php

namespace App\Services\Dashboard;

class LeadAsDashboardService
{
    public function getStats($user): array
    {
        return [
            'user' => [
                'role' => strtoupper($user->getRoleNames()->first()),
                'company' => $user->company_name,
                'image_path' => $user->image_path,
            ],
            'leads' => [
                'queries_count' => 120,
                'new_count' => 15,
                'replied_count' => 10,
            ],
            'shipments' => [
                'ongoing_count' => 8,
                'delivered_count' => 25,
            ],
            'quotations' => [
                'new_count' => 7,
                'responded_count' => 4,
                'accepted_count' => 5,
                'discarded_count' => 3,
            ],
            'accounts' => [
                'clients_count' => 50,
            ]
        ];
    }
}