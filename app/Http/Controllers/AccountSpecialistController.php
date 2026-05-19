<?php

namespace App\Http\Controllers;

use App\Enums\RoleType;
use App\Http\Resources\AccountSpecialistListResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AccountSpecialistController extends Controller
{
    public function __construct(private readonly UserService $userService)
    {
        //
    }

    /**
     * Index Account Specialists
     * 
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'filter.search' => 'sometimes|nullable|string',
            'filter.role' => 'sometimes|in:LEAD,REGULAR',
            'per_page' => 'sometimes|integer|min:1,max:100'
        ]);

        $perPage = $request->input('per_page', 10);

        $accountSpecialists = QueryBuilder::for(User::role(['Account Specialist', 'Lead Account Specialist']))
            ->allowedFilters([
                AllowedFilter::partial('search', 'full_name'),
                AllowedFilter::callback('role', function($query, $value) {
                    $role = match($value) {
                        'LEAD' => 'Lead Account Specialist',
                        'REGULAR' => 'Account Specialist',
                    };

                    $query->whereHas('roles', function($q) use ($role) {
                        $q->where('name', $role);
                    });
                }),
            ])
            ->with(['roles:id,name', 'latestQuotationAccepted'])
            ->withCount(
                [
                    'quotationsAccepted as request_accepted_count',
                    'issuedQuotations as quotation_sent_count',
                    'quotationsAccepted as qt_accepted_by_client_count' => fn ($q) =>
                        $q->where('status', 'ACCEPTED')
                ]
            )
            ->paginate($perPage);

        return $this->success(
            'Account Specialists fetched successfully',
            [
                'account_specialists' => AccountSpecialistListResource::collection($accountSpecialists->items()),
                'pagination' => $this->pagePaginationData($accountSpecialists)
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Account Specialist Summary
     * 
     * Provide summary of total account specialists and quoted quotations
     */
    public function summary()
    {
        return $this->success(
            'Account Specialists fetched successfully',
            $this->userService->getSummary(RoleType::ACCOUNT_SPECIALIST)
        );
    }
}
