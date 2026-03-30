<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\Quotation;
use App\Models\Shipment;

class AsDashboardService
{
    public function getStats($request, $user): array
    {
        // Get all clients (other users with Client role)
        $clientsCount = User::role('Client')->count();
        
        // Get shipments where this lead is the as_id
        // 'PENDING','NOT YET DELIVERED','IN TRANSIT','ARRIVED','BERTHED','DISCHARGED','DELIVERED'
        $pendingCount = Shipment::where('as_id', $user->id)->where('status', 'PENDING')->count();
        $notYetDeliveredCount = Shipment::where('as_id', $user->id)->where('status', 'NOT YET DELIVERED')->count();
        $inTransitCount = Shipment::where('as_id', $user->id)->where('status', 'IN TRANSIT')->count();
        $arrivedCount = Shipment::where('as_id', $user->id)->where('status', 'ARRIVED')->count();
        $dischargedCount = Shipment::where('as_id', $user->id)->where('status', 'DISCHARGED')->count();
        $berthedCount = Shipment::where('as_id', $user->id)->where('status', 'BERTHED')->count();

        $ongoingCount = $pendingCount + $notYetDeliveredCount + $inTransitCount + $arrivedCount + $dischargedCount + $berthedCount;

        $deliveredCount = Shipment::where('as_id', $user->id)->where('status', 'DELIVERED')->count();
        
        // Get quotations where this lead is the as_id
        $newCount = Quotation::where('as_id', $user->id)->where('status', 'REQUESTED')->count();
        $respondedCount = Quotation::where('as_id', $user->id)->where('status', 'RESPONDED')->count();
        $acceptedCount = Quotation::where('as_id', $user->id)->where('status', 'ACCEPTED')->count();
        $discardedCount = Quotation::where('as_id', $user->id)->where('status', 'DISCARDED')->count();

        if (strtolower($request->header('Platform', 'mobile') === 'web')) {
            $clientIds = Shipment::where('as_id', $user->id)->distinct()->pluck('client_id');
            $clients = [];

            foreach ($clientIds as $id) {
                $client = User::role('Client')
                    ->where('id', $id)
                    ->get();

                $idSection = 'C' . str_pad($id + 1, 3, '0', STR_PAD_LEFT) . - $client->value('created_at')->format('Y');

                $clients[] = [
                    'id' => $idSection,
                    'full_name' => $client->value('full_name'),
                    'total_shipment' => Shipment::where('client_id', $id)->count()
                ];
            }
            return [
                'quotations' => [
                    'responded_count' => $respondedCount,
                    'requested_count' => $newCount,
                    'accepted_count' => $acceptedCount,
                    'discarded_count' => $discardedCount,
                    'total_count' => $respondedCount + $newCount + $acceptedCount + $discardedCount
                ],
                'shipments' => [
                    'ongoing_count' => $ongoingCount,
                    'delivered_count' => $deliveredCount,
                    'total_count' => $ongoingCount + $deliveredCount
                ],
                'clients' => [
                    'total_count' => collect($clients)->count(),
                    'clients' => $clients
                ]
            ];
        }
        
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
                'accepted_count' => $acceptedCount,
                'discarded_count' => $discardedCount,
            ],
            'accounts' => [
                'clients_count' => $clientsCount,
            ]
        ];
    }
}