<?php

namespace App\Services\Dashboard;

use App\Models\JobOrder;
use App\Models\Shipment;

class OperationDashboardService
{
    public function getStats($user): array
    {
        $createdCount = JobOrder::where('shipment_creation_status', 'PENDING')->count();
        $processedCount = JobOrder::where('shipment_creation_status', 'CREATED')->count();

        $shipmentQuery = Shipment::whereIn(
            'quotation_id',
            JobOrder::where('operations_id', $user->id)->select('quotation_id')
        );

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