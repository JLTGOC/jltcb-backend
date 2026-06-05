<?php

namespace App\Repositories\JobOrder;

use App\Enums\ServiceLevelEnum;
use App\Models\IssuedQuotation\IssuedQuotation;
use App\Models\JobOrder;
use App\Models\User;
use App\Repositories\BaseRepository;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\Searchable\Search;
use App\Http\Resources\IndexJobOrder\{
    MobileJobOrderResource,
    WebLogisticsJobOrderResource,
    WebRegulatoryJobOrderResource,
};

class IndexJobOrderRepository extends BaseRepository
{
    public function execute($request){
        $platform = $request->header('Platform', 'mobile');
        $isWeb = $platform === 'web';

        $user = auth()->user();

        $allJobOrdersCount = JobOrder::count();
        $logisticsCount = JobOrder::where('job_type', 'LOGISTICS')->count();
        $regulatoryCount = JobOrder::where('job_type', 'REGULATORY')->count();

        [$jobOrdersQuery, $myJobOrdersQuery] = $this->buildBaseQueries($user, $isWeb);

        $jobOrders = $this->applyAllowedFilters($jobOrdersQuery);
        $myJobOrders = $myJobOrdersQuery ? $this->applyAllowedFilters($myJobOrdersQuery) : null;

        if ($request->has('search')) {
            [$jobOrders, $myJobOrders] = $this->applySearchFilters($request->search, $jobOrders, $myJobOrders);
        }

        if ($request->has('ops_search')) {
            [$jobOrders, $myJobOrders] = $this->applyOperationsSearchFilters($request->ops_search, $jobOrders, $myJobOrders);
        }

        if (isset($request->client_type) && in_array($request->client_type, ['OLD', 'NEW'])) {
            [$jobOrders, $myJobOrders] = $this->applyClientTypeFilters($request->client_type, $jobOrders, $myJobOrders);
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
            if ($isWeb) {
                if ($j->job_type === 'LOGISTICS') {
                    $serviceLevel = $j->jobOrderShipment->service_level ?? null;
                    $serviceLevel = $this->normalizeServiceLevel($serviceLevel);

                    return new WebLogisticsJobOrderResource($j, $serviceLevel);
                } elseif ($j->job_type === 'REGULATORY') {
                    return new WebRegulatoryJobOrderResource($j);
                }
            }
            return new MobileJobOrderResource($j);
        });

        if ($myJobOrders) {
            $myJobOrders = $myJobOrders->map(function ($j) use ($user, $isWeb) {
                if ($isWeb) {
                    if ($j->job_type === 'LOGISTICS') {
                        $serviceLevel = $j->jobOrderShipment->service_level ?? null;
                        $serviceLevel = $this->normalizeServiceLevel($serviceLevel);

                        return new WebLogisticsJobOrderResource($j, $serviceLevel);
                    } elseif ($j->job_type === 'REGULATORY') {
                        return new WebRegulatoryJobOrderResource($j);
                    }
                }
                return new MobileJobOrderResource($j);
            });
        }

        return $this->success('Job Orders fetched successfully', [
            'counts' => [
                'all_job_orders' => $allJobOrdersCount,
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

    private function buildBaseQueries($user, $isWeb): array
    {
        if ($user->hasRole('Lead Account Specialist')) {
            return [JobOrder::query()->whereNot('shipment_creation_status', 'CREATED'), null];
        } elseif ($user->hasRole('Account Specialist')) {
            return [JobOrder::query()->whereNot('shipment_creation_status', 'CREATED')->where('as_id', $user->id), null];
        } elseif ($user->hasRole(['Client Success'])) {
            if ($isWeb) {
                return [
                    JobOrder::query()->whereNot('shipment_creation_status', 'CREATED'),
                    JobOrder::query()->whereNot('shipment_creation_status', 'CREATED')->where('operations_id', $user->id),
                ];
            }
            return [
                JobOrder::query(),
                JobOrder::query()->where('operations_id', $user->id)
            ];
        } elseif ($user->hasRole(['Operations'])) {
            if ($isWeb) {
                return [
                    JobOrder::query()->whereNot('shipment_creation_status', 'CREATED')->whereNot('assignment_status', 'ASSIGNED'),
                    JobOrder::query()->whereNot('shipment_creation_status', 'CREATED')->where('operations_id', $user->id),
                ];
            }
            return [
                JobOrder::query()->whereNot('assignment_status', 'ASSIGNED'),
                JobOrder::query()->where('operations_id', $user->id),
            ];
        }
        return [JobOrder::query()->whereNot('shipment_creation_status', 'CREATED')->where('client_id', $user->id), null];
    }

    private function applyAllowedFilters($query): QueryBuilder
    {
        return QueryBuilder::for($query)
            ->allowedFilters([
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
                    } elseif ($value === 'AVAILABLE') {
                        return $query->where('assignment_status', 'AVAILABLE');
                    } elseif ($value === 'ASSIGNED') {
                        return $query->where('assignment_status', 'ASSIGNED');
                    } elseif ($value === 'REASSIGNMENT REQUESTED') {
                        return $query->where('assignment_status', 'REASSIGNMENT REQUESTED');
                    } elseif ($value === 'ALL') {
                        return $query; // No filtering, return all job orders
                    }
                }),
                AllowedFilter::exact('service_type', 'jobOrderClient.service_type'),
                AllowedFilter::callback('completion_status', function ($query, $value) {
                    if ($value === 'PROCESSED') {
                        return $query->where('shipment_creation_status', 'CREATED');
                    } elseif ($value === 'CREATED') {
                        return $query->where('shipment_creation_status', 'PENDING');
                    }
                }),
            ]);
    }

    private function applySearchFilters(string $searchTerm, QueryBuilder $jobOrders, ?QueryBuilder $myJobOrders): array
    {
        $search = (new Search())
            ->registerModel(JobOrder::class, ['reference_number'])
            ->search($searchTerm)
            ->pluck('searchable');

        $clientIds = User::where('full_name', 'like', '%' . $searchTerm . '%')->pluck('id');
        $clientJobOrderIds = JobOrder::whereIn('client_id', $clientIds)->pluck('id');
        $mergedIds = $search->pluck('id')->merge($clientJobOrderIds)->unique();

        $jobOrders = $jobOrders->whereIn('id', $mergedIds);

        if ($myJobOrders) {
            $myJobOrders = $myJobOrders->whereIn('id', $mergedIds);
        }

        return [$jobOrders, $myJobOrders];
    }

    private function applyOperationsSearchFilters(string $searchTerm, QueryBuilder $jobOrders, ?QueryBuilder $myJobOrders): array
    {
        $opsSearch = (new Search())
            ->registerModel(User::class, ['full_name'])
            ->search($searchTerm)
            ->pluck('searchable');

        $opsJobOrderIds = JobOrder::whereIn('operations_id', $opsSearch->pluck('id'))->pluck('id');

        if ($myJobOrders) {
            $myJobOrders = $myJobOrders->whereIn('id', $opsJobOrderIds);
        }

        $jobOrders = $jobOrders->whereIn('id', $opsJobOrderIds);

        return [$jobOrders, $myJobOrders];
    }

    private function applyClientTypeFilters(string $clientType, QueryBuilder $jobOrders, ?QueryBuilder $myJobOrders): array
    {
        $clientIds = $this->resolveClientIdsByType($clientType);

        $jobOrders = $jobOrders->whereIn('client_id', $clientIds);

        if ($myJobOrders) {
            $myJobOrders = $myJobOrders->whereIn('client_id', $clientIds);
        }

        return [$jobOrders, $myJobOrders];
    }

    private function resolveClientIdsByType(string $clientType): array
    {
        return User::role('Client')
            ->withCount('jobOrders')
            ->get()
            ->filter(function ($client) use ($clientType) {
                if ($clientType === 'OLD') {
                    return $client->job_orders_count > 1;
                }

                return $client->job_orders_count === 1;
            })
            ->pluck('id')
            ->all();
    }
}
