<?php

namespace App\Services;

use App\Models\JobOrder;
use App\Models\Quotation;
use App\Models\Shipment;
use App\Models\User;
use Carbon\Carbon;

class ClientService {
    public function getSummary() : array {
        return [
          'total_clients' => $this->totalClients(),
          'new_clients' => $this->newClientsThisMonth(),
          'active_shipments' => $this->activeShipments(),
          'active_regulatory' => $this->activeRegulatory(),
          'pending_quotations' => $this->pendingQuotations(),
        ];
    }

    private function totalClients() {
        return User::clients()->count();
    }

    private function newClientsThisMonth() {
        return User::clients()->where('created_at', '>=', Carbon::now()->subMonth())->count();
    }

    private function activeShipments() {
        return Shipment::whereNotIn('status', ['DELIVERED'])->count();
    }

    private function activeRegulatory() {
        return JobOrder::where('job_type', 'REGULATORY')->count();
    }

    private function pendingQuotations() {
        return Quotation::whereNotIn('status', ['ACCEPTED'])->count(); 
    }
}