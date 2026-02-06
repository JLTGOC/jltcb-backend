<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\Quotation;
use App\Models\Shipment;

class LeadAsDashboardService
{
    public function getStats($user): array
    {
        // Get all clients (other users with Client role)
        $clientsCount = User::role('Client')->count();
        
        // Get shipments where this lead is the as_id
        $ongoingCount = Shipment::where('as_id', $user->id)->where('status', 'ONGOING')->count();
        $deliveredCount = Shipment::where('as_id', $user->id)->where('status', 'DELIVERED')->count();
        
        // Get quotations where this lead is the as_id
        $newCount = Quotation::where('as_id', $user->id)->where('status', 'REQUESTED')->count();
        $respondedCount = Quotation::where('as_id', $user->id)->where('status', 'RESPONDED')->count();
        $acceptedCount = Quotation::where('as_id', $user->id)->where('status', 'ACCEPTED')->count();
        $discardedCount = Quotation::where('as_id', $user->id)->where('status', 'DISCARDED')->count();
        
        return [
            'user' => [
                'role' => strtoupper($user->getRoleNames()->first() ?? 'Unknown'),
                'company' => $user->company_name,
                'image_path' => $user->image_path,
            ],
            'leads' => [
                'queries_count' => 120,
                'new_count' => 15,
                'replied_count' => 10,
            ],
            'shipments' => [
                'ongoing_count' => $ongoingCount,
                'delivered_count' => $deliveredCount,
            ],
            'quotations' => [
                'new_count' => $newCount,
                'responded_count' => $respondedCount,
                'accepted_count' => $deliveredCount,
                'discarded_count' => $discardedCount,
            ],
            'accounts' => [
                'clients_count' => $clientsCount,
            ]
        ];
    }
}