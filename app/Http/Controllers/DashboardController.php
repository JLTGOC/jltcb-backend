<?php

namespace App\Http\Controllers;

use App\Enums\RoleType;
use Illuminate\Http\Request;
use App\Services\Dashboard\ClientDashboardService;
use App\Services\Dashboard\LeadAsDashboardService;
use App\Services\Dashboard\MarketingDashboardService;

class DashboardController extends Controller
{

    /**
     * Index Dashboard
     * 
     * Display dashboard data based on user role
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $primaryRole = $user->getRoleNames()->first();

        $data = match ($primaryRole) {
            RoleType::CLIENT->value => (new ClientDashboardService())->getStats($user),
            RoleType::ACCOUNT_SPECIALIST->value => (new LeadAsDashboardService())->getStats($user),
            RoleType::MARKETING->value => (new MarketingDashboardService())->getStats($user),
            default => ['message' => 'Generic dashboard data'],
        };

        return $this->success('Dashboard for ' . $primaryRole . ' retrieved successfully', $data, 200);
    }
}
