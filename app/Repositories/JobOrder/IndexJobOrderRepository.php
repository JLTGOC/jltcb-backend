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

        $myJobOrders = null;

        $allJobOrdersCount = JobOrder::count();
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
}
