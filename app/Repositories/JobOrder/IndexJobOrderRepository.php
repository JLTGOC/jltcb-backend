<?php

namespace App\Repositories\JobOrder;

use App\Enums\ServiceLevelEnum;
use App\Models\IssuedQuotation;
use App\Models\JobOrder;
use App\Models\User;
use App\Repositories\BaseRepository;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\Searchable\Search;

class IndexJobOrderRepository extends BaseRepository
{
    public function execute($request){
        $platform = $request->header('Platform', 'mobile');
        $isWeb = $platform === 'web';

        $user = auth()->user();

        $myJobOrders = null;

        $allJobOrdersCount = JobOrder::count();

        // $oldUsers = User::role('Client')->get()->filter(function ($client) {
        //     return $client->jobOrders->count() > 1;
        // })->pluck('id');
        // $oldUserJobOrdersCount = JobOrder::whereIn('client_id', $oldUsers)->count();

        // $newUsers = User::role('Client')->with('jobOrders')->get()->filter(function ($user) {
        //     return $user->jobOrders->count() === 1;
        // })->pluck('id');
        // $newUserJobOrdersCount = JobOrder::whereIn('client_id', $newUsers)->count();

        $logisticsCount = JobOrder::where('job_type', 'LOGISTICS')->count();
        $regulatoryCount = JobOrder::where('job_type', 'REGULATORY')->count();

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
                // AllowedFilter::exact('service', 'job_type'),
                AllowedFilter::callback('service', function ($query, $value) {
                    if ($value === 'LOGISTICS') {
                        return $query->where('job_type', 'LOGISTICS');
                    } elseif ($value === 'REGULATORY') {
                        return $query->where('job_type', 'REGULATORY');
                    } elseif ($value === 'ALL') {
                        return $query; // No filtering, return all job orders
                    }
                }),
                AllowedFilter::callback('assignment_status', function ($query, $value) {
                    if ($value === 'PENDING') {
                        return $query->where('assignment_status', 'AVAILABLE');
                    } elseif ($value === 'ACCEPTED') {
                        return $query->whereIn('assignment_status', ['ASSIGNED', 'REASSIGNMENT REQUESTED']);
                    }
                }),
                AllowedFilter::exact('service_type', 'jobOrderClient.service_type'),
            ]);

        $myJobOrders = $myJobOrdersQuery
            ? QueryBuilder::for($myJobOrdersQuery)->allowedFilters([
                AllowedFilter::exact('service', 'job_type'),
                AllowedFilter::callback('assignment_status', function ($query, $value) {
                    if ($value === 'PENDING') {
                        return $query->where('assignment_status', 'AVAILABLE');
                    } elseif ($value === 'ACCEPTED') {
                        return $query->whereIn('assignment_status', ['ASSIGNED', 'REASSIGNMENT REQUESTED']);
                    }
                }),
                AllowedFilter::exact('service_type', 'jobOrderClient.service_type'),
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
                        'client_type' => JobOrder::where('client_id', $j->client_id)->count() > 1 ? 'OLD' : 'NEW',
                        'date_created' => strtoupper($j->created_at->format('F d, Y')),
                        'job_type' => 'LOGISTICS',
                        'commodity' => $j->quotation->logisticsService->commodity,
                        'service_type' => $j->quotation->logisticsService->service_type,
                        'transport_mode' => $j->quotation->logisticsService->transport_mode,
                        'origin' => $j->quotation->logisticsService->origin,
                        'destination' => $j->quotation->logisticsService->destination,
                        'service_level' => $serviceLevel,
                        'bl_no' => $j->jobOrderShipment->bl_no ?? null,
                        'eta' => $j->jobOrderShipment->eta ? Carbon::parse($j->jobOrderShipment->eta)->format('M d, Y') : null,
                        'etd' => $j->jobOrderShipment->etd ? Carbon::parse($j->jobOrderShipment->etd)->format('M d, Y') : null,
                        'quotation_id' => $j->quotation_id,
                        'quotation_reference_number' => $j->quotation->reference_number,
                        'issued_quotation_id' => IssuedQuotation::where('quotation_id', $j->quotation_id)->value('id'),
                        'assignment_status' => $j->assignment_status,
                        'assigned_to' => $assignedTo,
                        'ops_image' => $j->operations ? asset(Storage::url($j->operations->image_path)) : null,
                        'assigned_at' => $j->operations_id ? mb_strtoupper(Carbon::parse($j->assigned_at)->format('F d, Y')) : null,
                        'reassignment_request_id' => $j->latestReassignmentRequest?->status !== 'PENDING' ? null : $j->latestReassignmentRequest->id,
                        'requested_at' => $j->latestReassignmentRequest?->status === 'PENDING' ? Carbon::parse($j->latestReassignmentRequest?->created_at)->format('F d, Y') : null,
                        'previously_assigned_to' => $j->latestReassignmentRequest?->status === 'APPROVED' 
                            ? mb_strtoupper($j->latestReassignmentRequest?->operations?->username) . ' ' . $j->latestReassignmentRequest?->operations?->last_name 
                            : null,
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
                        'client_type' => JobOrder::where('client_id', $j->client_id)->count() > 1 ? 'OLD' : 'NEW',
                        'date_created' => strtoupper($j->created_at->format('F d, Y')),
                        'job_type' => 'REGULATORY',
                        'application_type' => $j->quotation->regulatoryService->application_type,
                        'regulatory_assistance' => $j->jobOrderClient->service_type,
                        'quotation_id' => $j->quotation_id,
                        'quotation_reference_number' => $j->quotation->reference_number,
                        'issued_quotation_id' => IssuedQuotation::where('quotation_id', $j->quotation_id)->value('id'),
                        'assignment_status' => $j->assignment_status,
                        'assigned_to' => $assignedTo,
                        'ops_image' => $j->operations ? asset(Storage::url($j->operations->image_path)) : null,
                        'assigned_at' => $j->operations_id ? mb_strtoupper(Carbon::parse($j->assigned_at)->format('F d, Y')) : null,
                        'reassignment_request_id' => $j->latestReassignmentRequest?->status !== 'PENDING' ? null : $j->latestReassignmentRequest->id,
                        'requested_at' => $j->latestReassignmentRequest?->status === 'PENDING' ? Carbon::parse($j->latestReassignmentRequest?->created_at)->format('F d, Y') : null,
                        'previously_assigned_to' => $j->latestReassignmentRequest?->status === 'APPROVED' 
                            ? mb_strtoupper($j->latestReassignmentRequest?->operations?->username) . ' ' . $j->latestReassignmentRequest?->operations?->last_name 
                            : null,
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
                'client_type' => JobOrder::where('client_id', $j->client_id)->count() > 1 ? 'OLD' : 'NEW',
                'date_created' => strtoupper($j->created_at->format('F d, Y')),
                'quotation_id' => $j->quotation_id,
                'quotation_reference_number' => $j->quotation->reference_number,
                'assigned_to' => $assignedTo,
                'ops_image' => $j->operations ? asset(Storage::url($j->operations->image_path)) : null,
                'reassignment_request_id' => $j->latestReassignmentRequest?->status !== 'PENDING' ? null : $j->latestReassignmentRequest->id,
                'requested_at' => $j->latestReassignmentRequest?->status === 'PENDING' ? Carbon::parse($j->latestReassignmentRequest?->created_at)->format('F d, Y') : null,
                'previously_assigned_to' => $j->latestReassignmentRequest?->status === 'APPROVED' 
                    ? mb_strtoupper($j->latestReassignmentRequest?->operations?->username) . ' ' . $j->latestReassignmentRequest?->operations?->last_name 
                    : null,
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
                            'client_type' => JobOrder::where('client_id', $j->client_id)->count() > 1 ? 'OLD' : 'NEW',
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
                            'ops_image' => $j->operations ? asset(Storage::url($j->operations->image_path)) : null,
                            'assigned_at' => $j->operations_id ? mb_strtoupper(Carbon::parse($j->assigned_at)->format('F d, Y')) : null,
                            'reassignment_request_id' => $j->latestReassignmentRequest?->status !== 'PENDING' ? null : $j->latestReassignmentRequest->id,
                            'requested_at' => $j->latestReassignmentRequest?->status === 'PENDING' ? Carbon::parse($j->latestReassignmentRequest?->created_at)->format('F d, Y') : null,
                            'previously_assigned_to' => $j->latestReassignmentRequest?->status === 'APPROVED' 
                                ? mb_strtoupper($j->latestReassignmentRequest?->operations?->username) . ' ' . $j->latestReassignmentRequest?->operations?->last_name 
                                : null,
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
                            'client_type' => JobOrder::where('client_id', $j->client_id)->count() > 1 ? 'OLD' : 'NEW',
                            'date_created' => strtoupper($j->created_at->format('F d, Y')),
                            'job_type' => 'REGULATORY',
                            'application_type' => $j->quotation->regulatoryService->application_type,
                            'quotation_id' => $j->quotation_id,
                            'quotation_reference_number' => $j->quotation->reference_number,
                            'issued_quotation_id' => IssuedQuotation::where('quotation_id', $j->quotation_id)->value('id'),
                            'assignment_status' => $j->assignment_status,
                            'assigned_to' => $assignedTo,
                            'ops_image' => $j->operations ? asset(Storage::url($j->operations->image_path)) : null,
                            'assigned_at' => $j->operations_id ? mb_strtoupper(Carbon::parse($j->assigned_at)->format('F d, Y')) : null,
                            'reassignment_request_id' => $j->latestReassignmentRequest?->status !== 'PENDING' ? null : $j->latestReassignmentRequest->id,
                            'requested_at' => $j->latestReassignmentRequest?->status === 'PENDING' ? Carbon::parse($j->latestReassignmentRequest?->created_at)->format('F d, Y') : null,
                            'previously_assigned_to' => $j->latestReassignmentRequest?->status === 'APPROVED' 
                                ? mb_strtoupper($j->latestReassignmentRequest?->operations?->username) . ' ' . $j->latestReassignmentRequest?->operations?->last_name 
                                : null,
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
                    'client_type' => JobOrder::where('client_id', $j->client_id)->count() > 1 ? 'OLD' : 'NEW',
                    'date_created' => strtoupper($j->created_at->format('F d, Y')),
                    'quotation_id' => $j->quotation_id,
                    'quotation_reference_number' => $j->quotation->reference_number,
                    'issued_quotation_id' => IssuedQuotation::where('quotation_id', $j->quotation_id)->value('id'),
                    'assigned_to' => $assignedTo,
                    'ops_image' => $j->operations ? asset(Storage::url($j->operations->image_path)) : null,
                    'reassignment_request_id' => $j->latestReassignmentRequest?->status !== 'PENDING' ? null : $j->latestReassignmentRequest->id,
                    'requested_at' => $j->latestReassignmentRequest?->status === 'PENDING' ? Carbon::parse($j->latestReassignmentRequest?->created_at)->format('F d, Y') : null,
                    'previously_assigned_to' => $j->latestReassignmentRequest?->status === 'APPROVED' 
                        ? mb_strtoupper($j->latestReassignmentRequest?->operations?->username) . ' ' . $j->latestReassignmentRequest?->operations?->last_name 
                        : null,
                ];
            });
        }

        return $this->success('Job Orders fetched successfully', [
            'counts' => [
                'all_job_orders' => $allJobOrdersCount,
                // 'old_user_job_orders' => $oldUserJobOrdersCount,
                // 'new_user_job_orders' => $newUserJobOrdersCount,
                'logistics_job_orders' => $logisticsCount,
                'regulatory_job_orders' => $regulatoryCount,
            ],
            'job_orders' => $jobOrders,
            'my_job_orders' => $myJobOrders,
            'pagination' => $pagination,
            'my_job_orders_pagination' => $myJobOrdersPagination,
        ], 200);
    }

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
}
