<?php

namespace App\Http\Controllers;

use App\Enums\RoleType;
use App\Http\Resources\ClientDetailResource;
use App\Http\Resources\ClientListResource;
use App\Services\UserService;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ClientController extends Controller
{
    public function __construct(private readonly UserService $userService)
    {
        //
    }

    /**
     * Index Clients
     * 
     * Display a listing of the client accounts.
     */
    public function index(Request $request)
    {
        $request->validate([
            'filter.search' => 'sometimes|nullable|string|max:100',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'filter.date_created' => 'sometimes|date',
            'filter.type' => 'sometimes|string|in:OLD,NEW'
        ]);

        $perPage = $request->input('per_page', 10);

        $clients = QueryBuilder::for(User::clients())
            ->allowedFilters([
                AllowedFilter::partial('search', 'full_name'),
                AllowedFilter::callback(
                    'date_created',
                    function ($query, $value) {
                        $query->whereDate('created_at', $value);
                    }
                ),
                AllowedFilter::callback(
                    'type',
                    function($query, $value) {
                        $oneMonthAgo = Carbon::now()->subMonth();

                        match ($value) {
                            'NEW' => $query->where('created_at', '>=', $oneMonthAgo),
                            'OLD' => $query->where('created_at', '<', $oneMonthAgo),
                            default => null,
                        };
                    }
                )
            ])->paginate($perPage);

        $clients->loadCount([
            'quotations as pending_quotations_count' => fn($q) => 
                $q->whereNotIn('status', ['ACCEPTED']),
            'shipments as active_shipments_count' => fn($q) => 
                $q->whereNotIn('status', ['DELIVERED']),
            'jobOrders as active_regulatory_count' => fn($q) => 
                $q->where('job_type', 'REGULATORY'),
            'quotations as quotations_count'
        ]);

        return $this->success(
            'Client list fetched successfully',
            [
                'clients' => ClientListResource::collection($clients->items()),
                'pagination' => $this->pagePaginationData($clients)
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
     * Show Client Details
     * 
     * Display client details.
     */
    public function show(User $client)
    {
        $client->loadCount([
            'quotations as quotations_pending_count' => fn ($q) =>
                $q->whereNotIn('status', ['ACCEPTED']),
            'quotations as quotations_accepted_count' => fn ($q) =>
                $q->where('status', 'ACCEPTED'),

            'shipments as shipments_in_progress_count' => fn ($q) =>
                $q->whereNotIn('status', ['DELIVERED']),
            'shipments as shipments_completed_count' => fn ($q) =>
                $q->where('status', 'DELIVERED'),

            // temporary count for regulatory totals kase wala pa idea pano
            'jobOrders as regulatory_ongoing_count' => fn ($q) =>
                $q->where('job_type', 'REGULATORY'),
            'jobOrders as regulatory_completed_count' => fn ($q) =>
                $q->where('job_type', 'REGULATORY'),
        ]);

        return $this->success(
            'Client Details fetched successfully', 
            new ClientDetailResource($client)
        );
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
     * Client Summary
     * 
     * Display client summary details
     */
    public function summary()
    {
        return $this->success(
            'Client summary fetched successfully',
            $this->userService->getSummary(RoleType::CLIENT)
        );
    }

    /**
     * List Client Quotations
     * 
     * Display list client quotations
     */
    public function listQuotations(User $client) {
        return $client->quotations;
    }

    /**
     * List Client Shipments
     * 
     * Display list of client's shipments
     */
    public function listShipments(User $client) {
        return 'shipments';
    }

    /**
     * List Client Regulatory
     * 
     * Display list of client's regulatory
     */
    public function listRegulatory(User $client) {
        return 'regulatory';
    }
}
