<?php

namespace App\Services\Dashboard;

use App\Models\JobOrder;
use App\Models\Shipment;
use App\Models\Quotation;

class ClientSuccessDashboardService
{
    public function getStats($user): array
    {
        // Quotations
        $newCount = Quotation::where('status', 'REQUESTED')->where('as_id', $user->id)->whereDoesntHave('jobOrder')->count();
        $respondedCount = Quotation::where('status', 'RESPONDED')->where('as_id', $user->id)->whereDoesntHave('jobOrder')->count();
        $acceptedCount = Quotation::where('status', 'ACCEPTED')->where('as_id', $user->id)->whereDoesntHave('jobOrder')->count();
        $discardedCount = Quotation::where('status', 'DISCARDED')->where('as_id', $user->id)->whereDoesntHave('jobOrder')->count();

        // Job Orders
        $createdCount = JobOrder::where('operations_id', $user->id)->where('shipment_creation_status', 'PENDING')->count();
        $processedCount = JobOrder::where('operations_id', $user->id)->where('shipment_creation_status', 'CREATED')->count();

        // Shipments
        $shipmentQuery = Shipment::whereIn(
            'quotation_id',
            JobOrder::where('operations_id', $user->id)->select('quotation_id')
        );

        // Get ongoing and delivered shipment counts
        $ongoingCount = (clone $shipmentQuery)
            ->whereIn('status', ['PENDING', 'NOT YET DELIVERED', 'IN TRANSIT', 'ARRIVED', 'DISCHARGED', 'BERTHED'])
            ->count();

        $deliveredCount = (clone $shipmentQuery)
            ->where('status', 'DELIVERED')
            ->count();
        
        return [
            'user' => [
                'role' => strtoupper($user->getRoleNames()->first() ?? 'Unknown'),
                'company' => $user->company_name,
                'image_path' => $user->image_path,
            ],
            'quotations' => [
                'responded_count' => $respondedCount,
                'requested_count' => $newCount,
                'accepted_count' => $acceptedCount,
                'discarded_count' => $discardedCount,
            ],
            'job_orders' => [
                'total_count' => JobOrder::count(),
                'created_count' => $createdCount,
                'processed_count' => $processedCount,
            ],
            'shipments' => [
                'ongoing_count' => $ongoingCount,
                'delivered_count' => $deliveredCount,
            ],
        ];
    }
}