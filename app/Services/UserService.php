<?php

namespace App\Services;

use App\Enums\RoleType;
use App\Models\JobOrder;
use App\Models\Quotation;
use App\Models\Shipment;
use App\Models\User;
use Carbon\Carbon;

class UserService {
    public function getSummary(RoleType $roleType): array
    {
        $summary = [
            'active_shipments'   => $this->activeShipments(),
            'active_regulatory'  => $this->activeRegulatory(),
            'pending_quotations' => $this->pendingQuotations(),
        ];

        return match ($roleType) {
            RoleType::CLIENT => [
                ...$summary,
                'total_clients' => $this->totalClients(),
                'new_clients'   => $this->newClients(),
            ],

            RoleType::ACCOUNT_SPECIALIST => [
                ...$summary,
                'total_as' => $this->totalAS(),
            ],

            default => $summary,
        };
    }

    private function totalClients() {
        return User::clients()->count();
    }

    private function totalAS() {
        return User::role(['Account Specialist', 'Lead Account Specialist'])->count();
    }

    private function newClients() {
        return User::clients()->newClients()->count();
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