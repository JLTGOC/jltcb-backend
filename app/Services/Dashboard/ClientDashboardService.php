<?php

namespace App\Services\Dashboard;

use App\Models\Shipment;

class ClientDashboardService
{
    public function getStats($user): array
    {
        $pendingCount = Shipment::where('client_id', $user->id)->where('status', 'PENDING')->count();
        $notYetDeliveredCount = Shipment::where('client_id', $user->id)->where('status', 'NOT YET DELIVERED')->count();
        $inTransitCount = Shipment::where('client_id', $user->id)->where('status', 'IN TRANSIT')->count();
        $arrivedCount = Shipment::where('client_id', $user->id)->where('status', 'ARRIVED')->count();
        $dischargedCount = Shipment::where('client_id', $user->id)->where('status', 'DISCHARGED')->count();
        $berthedCount = Shipment::where('client_id', $user->id)->where('status', 'BERTHED')->count();

        $ongoingCount = $pendingCount + $notYetDeliveredCount + $inTransitCount + $arrivedCount + $dischargedCount + $berthedCount;
        
        return [
            'user' => [
                'full_name' => $user->full_name,
                'company' => $user->company_name,
                'image_path' => $user->image_path,
            ],
            'shipments' => [
                'ongoing_count' => $ongoingCount,
                'completed_count' => $user->shipments()->where('status', 'DELIVERED')->count(),
            ],
            'quotations' => [
                'requested_count' => $user->quotations()->where('status', 'REQUESTED')->count(),
                'responded_count' => $user->quotations()->where('status', 'RESPONDED')->count(),
            ],
        ];
    }
}