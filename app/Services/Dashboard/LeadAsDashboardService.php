<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\Quotation;
use App\Models\Shipment;
use Illuminate\Http\Request;

class LeadAsDashboardService
{
    public function getStats($request, $user): array
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

        if (strtolower($request->header('Platform', 'mobile') === 'web')) {
            $clientIds = Shipment::where('as_id', $user->id)->distinct()->pluck('client_id');
            $clients = [];

            foreach ($clientIds as $id) {
                $client = User::role('Client')
                    ->where('id', $id)
                    ->get();

                $id = 'C' . str_pad($id + 1, 3, '0', STR_PAD_LEFT) . - $client->value('created_at')->format('Y');

                $clients[] = [
                    'id' => $id,
                    'full_name' => $client->value('full_name'),
                    'total_shipment' => Shipment::where('client_id', $id)->count()
                ];
            }
            return [
                'quotations' => [
                    'responded_count' => $respondedCount,
                    'requested_count' => $newCount,
                    'total_count' => $respondedCount + $newCount
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
                'accepted_count' => $deliveredCount,
                'discarded_count' => $discardedCount,
            ],
            'accounts' => [
                'clients_count' => $clientsCount,
            ]
        ];
    }
}