<?php

namespace App\Http\Controllers;

use App\Enums\RoleType;
use App\Services\Dashboard\MarketingDashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $primaryRole = $user->getRoleNames()->first(); 

        $data = match ($primaryRole) {
            RoleType::MARKETING->value => (new MarketingDashboardService())->getStats($user),
            default                  => ['message' => 'Generic dashboard data'],
        };

        return $this->success('Dashboard for ' . $primaryRole . ' retrieved successfully', $data, 200);
    }
}
