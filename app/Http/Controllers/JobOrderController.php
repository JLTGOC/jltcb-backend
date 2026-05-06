<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\{
    StoreJobOrderRequest,
};
use App\Models\{
    User,
    JobOrder,
    Quotation,
    JobOrderClient,
    JobOrderShipment,
    JobOrderBilling,
    ServiceLevel,
    BillingMode,
    ReassignmentRequest,
    IssuedQuotation,
};
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Resources\{
    JobOrderResource,
    QuotationResource,
};
use Spatie\Searchable\Search;
use Carbon\Carbon;
use App\Enums\ServiceLevelEnum;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class JobOrderController extends Controller
{
    private function normalizeServiceLevel(?string $serviceLevel): ?string
    {
        if (!$serviceLevel) {
            return null;
        }

        return match ($serviceLevel) {
            'CC', 'CARGO CONSOLIDATION (CC)' => ServiceLevelEnum::CARGO_CONSOLIDATION->value,
            'DE', 'DIRECT EXPORT (DE)' => ServiceLevelEnum::DIRECT_EXPORT->value,
            'IFF', 'INTERNATIONAL FREIGHT FORWARDING (IFF)' => ServiceLevelEnum::INTERNATIONAL_FREIGHT_FORWARDING->value,
            'CC, DE', 'CARGO CONSOLIDATION (CC), DIRECT EXPORT (DE)' => ServiceLevelEnum::CARGO_CONSOLIDATION_DIRECT_EXPORT->value,
            'IFF, CC', 'INTERNATIONAL FREIGHT FORWARDING (IFF), CARGO CONSOLIDATION (CC)' => ServiceLevelEnum::INTERNATIONAL_FREIGHT_FORWARDING_CARGO_CONSOLIDATION->value,
            'IFF, CC, DE', 'INTERNATIONAL FREIGHT FORWARDING (IFF), CARGO CONSOLIDATION (CC), DIRECT EXPORT (DE)' => ServiceLevelEnum::INTERNATIONAL_FREIGHT_FORWARDING_CARGO_CONSOLIDATION_DIRECT_EXPORT->value,
            default => ServiceLevelEnum::tryFrom($serviceLevel)?->value ?? $serviceLevel,
        };
    }

    public function __construct()
    {
        $this->authorizeResource(JobOrder::class, 'job_order');
    }

    /**
     * Index Job Orders
     * 
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $platform = $request->header('Platform', 'mobile');
        $isWeb = $platform === 'web';

        $request->validate([
            'filter.service' => 'sometimes|string|in:LOGISTICS,REGULATORY',
            // 'filter.assignment_status' => 'sometimes|string|in:AVAILABLE,ASSIGNED,REASSIGNMENT REQUESTED',
            'search' => 'sometimes|string',
            'ops_search' => 'sometimes|string',
            'client_type' => 'sometimes|string|in:OLD,NEW',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'my_per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
            'my_page' => 'sometimes|integer|min:1',
        ]);

        $user = auth()->user();

        $myJobOrders = null;

        $allJobOrdersCount = JobOrder::count();

        $oldUsers = User::role('Client')->get()->filter(function ($client) {
            return $client->jobOrders->count() > 1;
        })->pluck('id');
        $oldUserJobOrdersCount = JobOrder::whereIn('client_id', $oldUsers)->count();

        $newUsers = User::role('Client')->with('jobOrders')->get()->filter(function ($user) {
            return $user->jobOrders->count() === 1;
        })->pluck('id');
        $newUserJobOrdersCount = JobOrder::whereIn('client_id', $newUsers)->count();

        if ($user->hasRole('Lead Account Specialist')) {
            $jobOrdersQuery = JobOrder::query();
            $myJobOrdersQuery = null;
        } elseif ($user->hasRole('Account Specialist')) {
            $jobOrdersQuery = JobOrder::query()->where('as_id', $user->id);
            $myJobOrdersQuery = null;
        } elseif ($user->hasRole('Lead Operations')) {
            $jobOrdersQuery = JobOrder::query();
            $myJobOrdersQuery = JobOrder::query()->where('operations_id', $user->id);
        } elseif ($user->hasRole('Operations')) {
            $jobOrdersQuery = JobOrder::query()->whereNot('assignment_status', 'ASSIGNED');
            $myJobOrdersQuery = JobOrder::query()->where('operations_id', $user->id);
        } else {
            $jobOrdersQuery = JobOrder::query()->where('client_id', $user->id);
            $myJobOrdersQuery = null;
        }

        $jobOrders = QueryBuilder::for($jobOrdersQuery)
            ->allowedFilters([
                AllowedFilter::exact('service', 'job_type'),
                // AllowedFilter::exact('assignment_status'),
            ]);

        $myJobOrders = $myJobOrdersQuery
            ? QueryBuilder::for($myJobOrdersQuery)->allowedFilters([
                AllowedFilter::exact('service', 'job_type'),
                // AllowedFilter::exact('assignment_status'),
            ])
            : null;

        if ($request->has('search')) {
            $search = (new Search())
                ->registerModel(JobOrder::class, ['reference_number'])
                ->search($request->search)
                ->pluck('searchable');

            $clientIds = User::where('full_name', 'like', '%' . $request->search . '%')->pluck('id');
            $clientJobOrderIds = JobOrder::whereIn('client_id', $clientIds)->pluck('id');
            $mergedIds = $search->pluck('id')->merge($clientJobOrderIds)->unique();

            $jobOrders = $jobOrders->whereIn('id', $mergedIds);

            if ($myJobOrders) {
                $myJobOrders = $myJobOrders->whereIn('id', $mergedIds);
            }
        }

        if ($request->has('ops_search')) {
            $opsSearch = (new Search())
                ->registerModel(User::class, ['full_name'])
                ->search($request->ops_search)
                ->pluck('searchable');

            $opsJobOrderIds = JobOrder::whereIn('operations_id', $opsSearch->pluck('id'))->pluck('id');
            if ($myJobOrders) {
                $myJobOrders = $myJobOrders->whereIn('id', $opsJobOrderIds);
            }
            $jobOrders = $jobOrders->whereIn('id', $opsJobOrderIds);
        }

        if (isset($request->client_type) && $request->client_type === 'OLD') {
            $oldClientIds = [];
            foreach (User::role('Client')->get() as $client) {
                if ($client->jobOrders->count() > 1) {
                    $oldClientIds[] = $client->id;
                }
            }
            $jobOrders = $jobOrders->whereIn('client_id', $oldClientIds);
            if ($myJobOrders) {
                $myJobOrders = $myJobOrders->whereIn('client_id', $oldClientIds);
            }
        } elseif (isset($request->client_type) && $request->client_type === 'NEW') {
            $newClientIds = [];
            foreach (User::role('Client')->get() as $client) {
                if ($client->jobOrders->count() === 1) {
                    $newClientIds[] = $client->id;
                }
            }
            $jobOrders = $jobOrders->whereIn('client_id', $newClientIds);
            if ($myJobOrders) {
                $myJobOrders = $myJobOrders->whereIn('client_id', $newClientIds);
            }
        }

        $jobOrders = $jobOrders->orderBy('created_at', 'desc')->get()->values();
        if ($myJobOrders) {
            $myJobOrders = $myJobOrders->orderBy('created_at', 'desc')->get()->values();
        }

        $pagination = null;
        $myJobOrdersPagination = null;

        if ($isWeb) {
            $paginateCollection = function ($items, int $perPage, int $page, string $pageName) use ($request) {
                return new LengthAwarePaginator(
                    $items->forPage($page, $perPage)->values(),
                    $items->count(),
                    $perPage,
                    $page,
                    [
                        'path' => $request->url(),
                        'pageName' => $pageName,
                    ]
                );
            };

            $jobPage = (int) $request->input('page', 1);
            $jobPerPage = (int) $request->input('per_page', 10);
            $jobOrdersPaginator = $paginateCollection($jobOrders, $jobPerPage, $jobPage, 'page');
            $jobOrders = $jobOrdersPaginator->getCollection();
            $pagination = $this->pagePaginationData($jobOrdersPaginator);

            if ($myJobOrders) {
                $myPage = (int) $request->input('my_page', 1);
                $myPerPage = (int) $request->input('my_per_page', $jobPerPage);
                $myJobOrdersPaginator = $paginateCollection($myJobOrders, $myPerPage, $myPage, 'my_page');
                $myJobOrders = $myJobOrdersPaginator->getCollection();
                $myJobOrdersPagination = $this->pagePaginationData($myJobOrdersPaginator);
            }
        }

        $jobOrders = $jobOrders->map(function ($j) use ($user, $isWeb) {
            if ($j->job_type === 'LOGISTICS') {
                $service = 'Logistics Services';
            } elseif ($j->job_type === 'REGULATORY') {
                $service = 'Regulatory Services';
            } else {
                $service = 'N/A';
            }

            $assignedTo = $j->operations ? $j->operations->username : 'Available';

            if ($isWeb) {
                if ($j->job_type === 'LOGISTICS') {
                    $serviceLevel = $j->jobOrderShipment->service_level ?? null;
                    $serviceLevel = $this->normalizeServiceLevel($serviceLevel);

                    if ($j->operations) {
                        $assignedTo = mb_strtoupper($j->operations->full_name);
                    } else {
                        $assignedTo = null;
                    }

                    return [
                        'id' => $j->id,
                        'reference_number' => $j->reference_number,
                        'client' => $j->client->full_name,
                        'date_created' => strtoupper($j->created_at->format('F d, Y')),
                        'job_type' => 'LOGISTICS',
                        'commodity' => $j->quotation->logisticsService->commodity,
                        'service_type' => $j->quotation->logisticsService->service_type,
                        'transport_mode' => $j->quotation->logisticsService->transport_mode,
                        'origin' => $j->quotation->logisticsService->origin,
                        'destination' => $j->quotation->logisticsService->destination,
                        'service_level' => $serviceLevel,
                        'bl_no' => $j->jobOrderShipment->bl_no ?? null,
                        'quotation_id' => $j->quotation_id,
                        'quotation_reference_number' => $j->quotation->reference_number,
                        'issued_quotation_id' => IssuedQuotation::where('quotation_id', $j->quotation_id)->value('id'),
                        'assignment_status' => $j->assignment_status,
                        'assigned_to' => $assignedTo,
                        'ops_image' => $j->operations ? asset($j->operations->image_path) : null,
                        'assigned_at' => $j->operations_id ? mb_strtoupper(Carbon::parse($j->assigned_at)->format('F d, Y')) : null,
                        'reassignment_request_id' => $j->latestReassignmentRequest ? $j->latestReassignmentRequest->id : null,
                        'generate_shipment' => $j->operations_id === $user->id && !$j->shipment && $j->assignment_status === 'ASSIGNED' ? true : false,
                        'shipment_creation_status' => $j->shipment_creation_status,
                    ];
                } elseif ($j->job_type === 'REGULATORY') {
                    if ($j->operations) {
                        $assignedTo = mb_strtoupper($j->operations->full_name);
                    } else {
                        $assignedTo = null;
                    }
                    return [
                        'id' => $j->id,
                        'reference_number' => $j->reference_number,
                        'client' => $j->client->full_name,
                        'date_created' => strtoupper($j->created_at->format('F d, Y')),
                        'job_type' => 'REGULATORY',
                        'application_type' => $j->quotation->regulatoryService->application_type,
                        'quotation_id' => $j->quotation_id,
                        'quotation_reference_number' => $j->quotation->reference_number,
                        'issued_quotation_id' => IssuedQuotation::where('quotation_id', $j->quotation_id)->value('id'),
                        'assignment_status' => $j->assignment_status,
                        'assigned_to' => $assignedTo,
                        'ops_image' => $j->operations ? asset($j->operations->image_path) : null,
                        'assigned_at' => $j->operations_id ? mb_strtoupper(Carbon::parse($j->assigned_at)->format('F d, Y')) : null,
                        'reassignment_request_id' => $j->latestReassignmentRequest ? $j->latestReassignmentRequest->id : null,
                        'generate_shipment' => false, // REGULATORY job orders should not have the option to generate shipment
                        'shipment_creation_status' => $j->shipment_creation_status,
                    ];
                }
            }

            return [
                'id' => $j->id,
                'reference_number' => $j->reference_number,
                'service' => $service,
                'client' => $j->client->full_name,
                'date_created' => strtoupper($j->created_at->format('F d, Y')),
                'quotation_id' => $j->quotation_id,
                'quotation_reference_number' => $j->quotation->reference_number,
                'assigned_to' => $assignedTo,
                'ops_image' => $j->operations ? asset($j->operations->image_path) : null,
                'reassignment_request_id' => $j->latestReassignmentRequest ? $j->latestReassignmentRequest->id : null,
            ];
        });

        if ($myJobOrders) {
            $myJobOrders = $myJobOrders->map(function ($j) use ($user, $isWeb) {
                if ($j->job_type === 'LOGISTICS') {
                    $service = 'Logistics Services';
                } elseif ($j->job_type === 'REGULATORY') {
                    $service = 'Regulatory Services';
                } else {
                    $service = 'N/A';
                }

                $assignedTo = $j->operations ? $j->operations->username : 'Available';

                if ($isWeb) {
                    if ($j->job_type === 'LOGISTICS') {
                        $serviceLevel = $j->jobOrderShipment->service_level ?? null;
                        $serviceLevel = $this->normalizeServiceLevel($serviceLevel);

                        if ($j->operations) {
                            $assignedTo = mb_strtoupper($j->operations->full_name);
                        } else {
                            $assignedTo = null;
                        }

                        return [
                            'id' => $j->id,
                            'reference_number' => $j->reference_number,
                            'client' => $j->client->full_name,
                            'date_created' => strtoupper($j->created_at->format('F d, Y')),
                            'job_type' => 'LOGISTICS',
                            'commodity' => $j->quotation->logisticsService->commodity,
                            'service_type' => $j->quotation->logisticsService->service_type,
                            'transport_mode' => $j->quotation->logisticsService->transport_mode,
                            'origin' => $j->quotation->logisticsService->origin,
                            'destination' => $j->quotation->logisticsService->destination,
                            'service_level' => $serviceLevel,
                            'bl_no' => $j->jobOrderShipment->bl_no ?? null,
                            'quotation_id' => $j->quotation_id,
                            'quotation_reference_number' => $j->quotation->reference_number,
                            'issued_quotation_id' => IssuedQuotation::where('quotation_id', $j->quotation_id)->value('id'),
                            'assignment_status' => $j->assignment_status,
                            'assigned_to' => $assignedTo,
                            'ops_image' => $j->operations ? asset($j->operations->image_path) : null,
                            'assigned_at' => $j->operations_id ? mb_strtoupper(Carbon::parse($j->assigned_at)->format('F d, Y')) : null,
                            'reassignment_request_id' => $j->latestReassignmentRequest ? $j->latestReassignmentRequest->id : null,
                            'generate_shipment' => $j->operations_id === $user->id && !$j->shipment && $j->assignment_status === 'ASSIGNED' ? true : false,
                            'shipment_creation_status' => $j->shipment_creation_status,
                        ];
                    } elseif ($j->job_type === 'REGULATORY') {
                        if ($j->operations) {
                            $assignedTo = mb_strtoupper($j->operations->full_name);
                        } else {
                            $assignedTo = null;
                        }
                        return [
                            'id' => $j->id,
                            'reference_number' => $j->reference_number,
                            'client' => $j->client->full_name,
                            'date_created' => strtoupper($j->created_at->format('F d, Y')),
                            'job_type' => 'REGULATORY',
                            'application_type' => $j->quotation->regulatoryService->application_type,
                            'quotation_id' => $j->quotation_id,
                            'quotation_reference_number' => $j->quotation->reference_number,
                            'issued_quotation_id' => IssuedQuotation::where('quotation_id', $j->quotation_id)->value('id'),
                            'assignment_status' => $j->assignment_status,
                            'assigned_to' => $assignedTo,
                            'ops_image' => $j->operations ? asset($j->operations->image_path) : null,
                            'assigned_at' => $j->operations_id ? mb_strtoupper(Carbon::parse($j->assigned_at)->format('F d, Y')) : null,
                            'reassignment_request_id' => $j->latestReassignmentRequest ? $j->latestReassignmentRequest->id : null,
                            'generate_shipment' => false, // REGULATORY job orders should not have the option to generate shipment
                            'shipment_creation_status' => $j->shipment_creation_status,
                        ];
                    }
                }

                return [
                    'id' => $j->id,
                    'reference_number' => $j->reference_number,
                    'service' => $service,
                    'client' => $j->client->full_name,
                    'date_created' => strtoupper($j->created_at->format('F d, Y')),
                    'quotation_id' => $j->quotation_id,
                    'quotation_reference_number' => $j->quotation->reference_number,
                    'issued_quotation_id' => IssuedQuotation::where('quotation_id', $j->quotation_id)->value('id'),
                    'assigned_to' => $assignedTo,
                    'ops_image' => $j->operations ? asset($j->operations->image_path) : null,
                    'reassignment_request_id' => $j->latestReassignmentRequest ? $j->latestReassignmentRequest->id : null,
                ];
            });
        }

        return $this->success('Job Orders fetched successfully', [
            'counts' => [
                'all_job_orders' => $allJobOrdersCount,
                'old_user_job_orders' => $oldUserJobOrdersCount,
                'new_user_job_orders' => $newUserJobOrdersCount,
            ],
            'job_orders' => $jobOrders,
            'my_job_orders' => $myJobOrders,
            'pagination' => $pagination,
            'my_job_orders_pagination' => $myJobOrdersPagination,
        ], 200);
    }

    /**
     * Store Job Order
     * 
     * Store a newly created resource in storage.
     */
    public function store(StoreJobOrderRequest $request)
    {
        $quotation = Quotation::where('reference_number', $request->quotation_reference_number)->first();

        if (!$quotation) {
            return $this->error('Quotation not found', 404);
        }
        if ($quotation->as_id !== auth()->id() && !auth()->user()->hasRole('Lead Account Specialist')) {
            return $this->error('You are not authorized to create a Job Order for this quotation', 403);
        }
        if (JobOrder::where('quotation_id', $quotation->id)->exists()) {
            return $this->error('A Job Order has already been created for this quotation', 400);
        }
        if ($quotation->status !== 'ACCEPTED') {
            return $this->error('Job Orders can only be created for quotations in ACCEPTED status', 400);
        }
        
        $idSection = str_pad(((JobOrder::latest('id')->value('id') ?? 0) + 1), 3, '0', STR_PAD_LEFT);
        $dateSection = now()->format('m-Y');

        if ($request->job_type === 'LOGISTICS') {
            if ($quotation->regulatoryService) {
                return $this->error('This job type can only be created for logistics quotations', 422);
            }

            $prefix = 'SJO';
            $referenceNumber = "{$prefix}-{$dateSection}-{$idSection}";

            try {
                $jobOrder = JobOrder::create([
                    'reference_number' => $referenceNumber,
                    'job_type' => $request->job_type,
                    'client_id' => $quotation->client_id,
                    'as_id' => $quotation->as_id,
                    'quotation_id' => $quotation->id,
                    'subject' => $request->subject['subject'],
                    'email_body' => $request->subject['email_body'],
                ]);

                JobOrderClient::create([
                    'job_order_id' => $jobOrder->id,
                    'client_type' => $request->client['client_type'],
                    'accredited' => $request->client['accredited'],
                    'service_type' => $quotation->logisticsService->service_type,
                    'client_remarks' => $request->client['remarks'] ?? null,
                ]);

                JobOrderShipment::create([
                    'job_order_id' => $jobOrder->id,
                    'service_level' => $request->service['service_level'],
                    'bl_no' => $request->service['bl_no'],
                    'eta' => $request->service['eta'],
                    'etd' => $request->service['etd'],
                    'if_coordinated' => $request->shipment['if_coordinated'] ?? null,
                    'hs_code' => $request->shipment['hs_code'] ?? null,
                    'rod' => $request->shipment['rod'] ?? null,
                    'permits' => $request->shipment['permits'] ?? null,
                    'shipment_remarks' => $request->shipment['special_remarks'] ?? null,
                    'target_delivery_date' => $request->target['delivery_date'] ?? null,
                    'target_completion_date' => $request->target['completion_date'] ?? null,
                    'commitment_remarks' => $request->target['special_remarks'] ?? null,
                ]);

                JobOrderBilling::create([
                    'job_order_id' => $jobOrder->id,
                    'terms_of_payment' => $request->billing['terms_of_payment'] ?? null,
                    'billing_date' => $request->billing['billing_date'] ?? null,
                    'shall_be_billed' => $request->billing['shall_be_billed'] ?? null,
                ]);

                DB::commit();
                return $this->success('Job Order created successfully', new JobOrderResource($jobOrder), 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->error('Something went wrong' . $e->getMessage(), 500);
            }
        } elseif ($request->job_type === 'REGULATORY') {
            if ($quotation->logisticsService) {
                return $this->error('This job type can only be created for regulatory quotations', 422);
            }

            $prefix = 'SPL';
            $referenceNumber = "{$prefix}-{$dateSection}-{$idSection}";

            try {
                $jobOrder = JobOrder::create([
                    'reference_number' => $referenceNumber,
                    'job_type' => $request->job_type,
                    'client_id' => $quotation->client_id,
                    'as_id' => $quotation->as_id,
                    'quotation_id' => $quotation->id,
                    'subject' => $request->subject['subject'],
                    'email_body' => $request->subject['email_body'],
                ]);

                JobOrderClient::create([
                    'job_order_id' => $jobOrder->id,
                    'client_type' => $request->client['client_type'],
                    'accredited' => $request->client['accredited'],
                    'service_type' => $request->client['service_type'],
                    'client_remarks' => $request->client['remarks'] ?? null,
                ]);

                DB::commit();
                return $this->success('Job Order created successfully', new JobOrderResource($jobOrder), 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->error('Something went wrong', $e->getMessage(), 500);
            }
            
        } else {
            return $this->error('Invalid job type', 422);
        }
    }

    /**
     * Show Job Order
     * 
     * Display the specified resource.
     */
    public function show(JobOrder $jobOrder)
    {
        if (!$jobOrder) {
            return $this->error('Job Order not found', 404);
        }

        return $this->success('Job Order fetched successfully', new JobOrderResource($jobOrder), 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, JobOrder $jobOrder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(JobOrder $jobOrder)
    {
        //
    }

    /**
     * Get Job Order Enums
     * 
     * Fetch enums for Job Order creation form
     */
    public function jobOrderEnums(Request $request) {
        $this->authorize('jobOrderEnums', JobOrder::class);

        $autofillDetails = null;
        
        $request->validate([
            'quotation_reference_number' => 'sometimes|string'
        ]);
        if ($request->has('quotation_reference_number')) {
            $quotation = Quotation::where('reference_number', $request->quotation_reference_number)->first() ?? null;
            $client = $quotation->client ?? null;
            $autofillDetails = [
                'company_name' => $client->company_name ?? null,
                'full_name' => $client->full_name ?? null,
                'commodity' => $quotation->logisticsService?->commodity ?? null,
                'cargo_type' => $quotation->logisticsService?->cargo_type ?? null,
                'container_size' => $quotation->logisticsService?->container_size ?? null,
            ];
        }
            
        $clientTypes = ['NEW', 'RENEWAL'];
        $jobTypes = ['LOGISTICS', 'REGULATORY'];
        $accredited = ['REGULAR', 'EXPEDITED'];
        $serviceLevels = ServiceLevel::pluck('name');
        $shallBeBilled = BillingMode::pluck('name');

        return $this->success('Job Order Enums fetched successfully', [
            'autofill_details' => $autofillDetails,
            'job_types' => $jobTypes,
            'client_types' => $clientTypes,
            'accredited' => $accredited,
            'service_levels' => $serviceLevels,
            'shall_be_billed' => $shallBeBilled,
        ]);
    }

    /**
     * Show Job Order Quotation
     * 
     * Fetch the quotation details associated with a Job Order
     */
    public function showJobOrderQuotation(JobOrder $jobOrder) {
        $this->authorize('showJobOrderQuotation', $jobOrder);
        
        if (!$jobOrder) {
            return $this->error('Job Order not found', 404);
        }

        $quotation = $jobOrder->quotation;

        if (!$quotation) {
            return $this->error('Quotation not found for this Job Order', 404);
        }

        return $this->success('Quotation fetched successfully', new QuotationResource($quotation), 200);
    }

    /**
     * Accept Job Order
     * 
     * Accept a Job Order and assign it to the authenticated Operations user
     */
    public function acceptJobOrder(JobOrder $jobOrder) {
        $this->authorize('acceptJobOrder', $jobOrder);

        if (!$jobOrder) {
            return $this->error('Job Order not found', 404);
        }

        $user = auth()->user();
        $jobOrder->update([
            'operations_id' => $user->id,
            'assignment_status' => 'ASSIGNED',
            'assigned_at' => Carbon::now(),
        ]);

        return $this->success('Job Order accepted successfully', new JobOrderResource($jobOrder), 200);
    }

    /**
     * Request Job Order Reassignment
     * 
     * Request reassignment of the Job Order to another Operations user (Operations users) or request reassignment to Operations team (Lead Operations)
     */
    public function requestReassignment(JobOrder $jobOrder, Request $request) {
        $this->authorize('requestReassignment', $jobOrder);

        if (!$jobOrder) {
            return $this->error('Job Order not found', 404);
        }

        $reassignmentRequest = ReassignmentRequest::where('job_order_id', $jobOrder->id)->where('status', 'PENDING')->latest()->first();

        if ($reassignmentRequest) {
            return $this->error('A reassignment request is already pending for this Job Order', 422);
        } else {
            $request->validate([
                'reason' => 'required|string|in:WORKLOAD,EMERGENCY / LEAVE,CLIENT REQUEST',
                'additional_details' => 'sometimes|string',
            ]);

            $jobOrder->update([
                'assignment_status' => 'REASSIGNMENT REQUESTED',
            ]);

            $reassignmentRequest = ReassignmentRequest::create([
                'job_order_id' => $jobOrder->id,
                'as_id' => auth()->id(),
                'reason' => $request->reason,
                'additional_details' => $request->additional_details,
                'status' => 'PENDING',
            ]);

            return $this->success('Reassignment request submitted successfully', $reassignmentRequest, 200);
        }
    }

    /**
     * Reassign Job Order Operations
     * 
     * Reassign the Job Order to another Operations user
     */
    public function reassignOps(Request $request, JobOrder $jobOrder) {
        $this->authorize('reassignOps', $jobOrder);

        if (!$jobOrder) {
            return $this->error('Job Order not found', 404);
        }

        $reassignmentRequest = ReassignmentRequest::where('job_order_id', $jobOrder->id)->where('status', 'PENDING')->latest()->first();

        if (!$reassignmentRequest) {
            return $this->error('No pending reassignment request for this Job Order', 422);
        }

        $request->validate([
            'status' => 'required|in:APPROVED,REJECTED',
            'operations_id' => 'required_if:status,APPROVED|exists:users,id'
        ]);

        if ($request->status === 'REJECTED') {
            $jobOrder->update([
                'assignment_status' => 'ASSIGNED',
            ]);

            $reassignmentRequest->update([
                'status' => 'REJECTED',
            ]);

            return $this->success('Reassignment request rejected', $reassignmentRequest, 200);
        } elseif ($request->status === 'APPROVED') {
            $user = User::find($request->operations_id);
            
            if (!$user || !$user->hasRole('Operations')) {
                return $this->error('The selected user is not an Operations user', 422);
            }
            if ((int) $request->operations_id === $jobOrder->operations_id) {
                return $this->error('The Job Order is already assigned to this Operations user', 422);
            }

            $jobOrder->update([
                'operations_id' => $request->operations_id,
                'assigned_at' => Carbon::now(),
                'assignment_status' => 'ASSIGNED'
            ]);

            $reassignmentRequest->update([
                'status' => 'APPROVED',
            ]);

            return $this->success('Reassignment request approved', $reassignmentRequest, 200);
        }
    }
}
