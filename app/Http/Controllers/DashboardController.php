<?php

namespace App\Http\Controllers;

use App\Enums\RoleType;
use App\Services\Dashboard\AsDashboardService;
use App\Services\Dashboard\ClientDashboardService;
use App\Services\Dashboard\LeadAsDashboardService;
use App\Services\Dashboard\MarketingDashboardService;
use App\Services\Dashboard\OperationDashboardService;
use App\Services\Dashboard\ClientSuccessDashboardService;
use Illuminate\Http\Request;

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
            RoleType::ACCOUNT_SPECIALIST->value => (new AsDashboardService())->getStats($request, $user),
            RoleType::LEAD_ACCOUNT_SPECIALIST->value => (new LeadAsDashboardService())->getStats($request, $user),
            RoleType::MARKETING->value => (new MarketingDashboardService())->getStats($user),
            RoleType::OPERATIONS->value => (new OperationDashboardService())->getStats($user),
            RoleType::LEAD_OPERATIONS->value => (new OperationDashboardService())->getStats($user),
            RoleType::CLIENT_SUCCESS->value => (new ClientSuccessDashboardService())->getStats($user),
            RoleType::LEAD_CLIENT_SUCCESS->value => (new ClientSuccessDashboardService())->getStats($user),
            default => ['message' => 'Generic dashboard data'],
        };

        return $this->success('Dashboard for ' . $primaryRole . ' retrieved successfully', $data, 200);
    }
}
