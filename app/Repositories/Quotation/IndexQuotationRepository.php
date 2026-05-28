<?php

namespace App\Repositories\Quotation;

use App\Http\Resources\QuotationResource;
use App\Http\Resources\IndexQuotation\{
    WebQuotationResource,
    MobileRequestedQuotationCollection,
    MobileQuotationResource,
    ClientQuotationResource,
};
use App\Models\IssuedQuotation;
use App\Models\Message;
use App\Models\Quotation;
use App\Models\User;
use App\Repositories\BaseRepository;
use Carbon\Carbon;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\Searchable\Search;
use Illuminate\Support\Facades\Storage;

class IndexQuotationRepository extends BaseRepository
{
    public function execute($request){
        $user = auth()->user();
        $request->validated();
        $platform = strtolower((string) $request->header('Platform', 'mobile'));
        $isWeb = $platform === 'web';
        $perPage = $request->input('per_page', 10);
        $myPerPage = $request->input('my_per_page', $perPage);
        $dateFormat = $isWeb ? 'F d, Y' : 'Y/m/d';
        $queryData = $this->buildRoleBasedQueries($user, $isWeb);
        if (!$queryData) {
            return $this->error('Unauthorized', 403);
        }

        $query = $queryData['query'];
        $myQuotationsQuery = $queryData['my_quotations_query'];

        $quotations = $this->buildQuotationQueryBuilder($query);
        $myQuotations = $this->buildMyQuotationQueryBuilder($myQuotationsQuery);

        $countQuery = clone $quotations;

        $this->applyQueryConstraints($quotations, $request);
        if ($myQuotations) {
            $this->applyQueryConstraints($myQuotations, $request, false);
        }

        $pagination = null;
        $myQuotationsPagination = null;

        if ($user->hasRole(['Account Specialist', 'Lead Account Specialist', 'Operations', 'Lead Operations', 'Client Success', 'Lead Client Success'])) {
            if ($isWeb) {
                $formatQuotation = function ($quotation) {
                    return (new WebQuotationResource($quotation))->toArray(request());
                };

                $resultsQuery = $quotations
                    ->with(['client', 'accountSpecialist', 'logisticsService', 'regulatoryService'])
                    ->orderBy('created_at', 'desc');

                $paginated = $resultsQuery->paginate($perPage);

                $quotations = $paginated->getCollection()->map($formatQuotation);

                $myQuotationsResults = collect();
                if ($myQuotations) {
                    $myPage = $request->input('my_page', 1);
                    $myPaginated = $myQuotations
                        ->with(['client', 'accountSpecialist', 'logisticsService', 'regulatoryService'])
                        ->orderBy('created_at', 'desc')
                        ->paginate($myPerPage, ['*'], 'my_page', $myPage);

                    $myQuotationsResults = $myPaginated->getCollection()->map($formatQuotation)->values();
                    $myQuotationsPagination = $this->pagePaginationData($myPaginated);
                }

                $pagination = $this->pagePaginationData($paginated);

                $allQuotationsCount = (clone $countQuery)->count();

                $oldClientsCount = (clone $countQuery)
                    ->whereHas('client', function ($query) {
                        $query->has('quotations', '>', 1);
                    })
                    ->count();

                $newClientsCount = (clone $countQuery)
                    ->whereHas('client', function ($query) {
                        $query->has('quotations', '<=', 1);
                    })
                    ->count();

                if ($quotations->isEmpty() && $myQuotationsResults->isEmpty()) {
                    return $this->success('No quotations found', [
                        'counts' => [
                            'all_quotations' => $allQuotationsCount,
                            'old_user_quotations' => $oldClientsCount,
                            'new_user_quotations' => $newClientsCount,
                        ],
                        'quotations' => [],
                        'my_quotations' => $myQuotationsResults,
                        'pagination' => $pagination,
                        'my_quotations_pagination' => $myQuotationsPagination,
                    ], 200);
                }

                return $this->success('All quotations fetched', [
                    'counts' => [
                        'all_quotations' => $allQuotationsCount,
                        'old_user_quotations' => $oldClientsCount,
                        'new_user_quotations' => $newClientsCount,
                    ],
                    'quotations' => $quotations->values(),
                    'my_quotations' => $myQuotationsResults,
                    'pagination' => $pagination,
                    'my_quotations_pagination' => $myQuotationsPagination,
                ], 200);
            } else {
                if ($request->filter['status'] === 'REQUESTED') {
                    $resultsQuery = $quotations
                        ->with(['client', 'accountSpecialist', 'logisticsService', 'regulatoryService'])
                        ->orderBy('created_at', 'desc');

                    $results = $resultsQuery->get();

                    $groupedByClient = $results->groupBy('client_id')->map(function ($clientQuotations) {
                        return (new MobileRequestedQuotationCollection($clientQuotations))->toArray(request());
                    })->values();

                    if ($groupedByClient->isEmpty()) {
                        return $this->success('No quotations found', [], 200);
                    }

                    return $this->success('All quotations fetched', $groupedByClient, 200);
                } else {
                    $resultsQuery = $quotations->with(['client', 'logisticsService'])->orderBy('created_at', 'desc');

                    $results = $resultsQuery->get()->map(function ($result) {
                        return (new MobileQuotationResource($result))->toArray(request());
                    });

                    if ($results->isEmpty()) {
                        return $this->success('No quotations found', [], 200);
                    }

                    return $this->success('All quotations fetched', $results->values(), 200);
                }
            }
        } else {
            $resultsQuery = $quotations->with(['client', 'logisticsService'])->orderBy('created_at', 'desc');

            if ($isWeb) {
                $paginated = $resultsQuery->paginate($perPage);
                $pagination = $this->pagePaginationData($paginated);
                $results = $paginated->getCollection();
            } else {
                $results = $resultsQuery->get();
            }

            $results = $results->map(function ($result) {
                return new ClientQuotationResource($result);
            });

            return $this->success('All quotations fetched', $results->values(), 200);
        }
    }

    private function buildRoleBasedQueries($user, bool $isWeb): ?array
    {
        $query = Quotation::query();
        $myQuotationsQuery = null;

        if ($user->hasRole('Client')) {
            $query->where('client_id', $user->id);
        } elseif ($user->hasRole('Lead Account Specialist')) {
            if ($isWeb) {
                $query->where(function ($q) use ($user) {
                    $q->whereNull('as_id')
                    ->orWhere('as_id', '!=', $user->id);
                });;
                $myQuotationsQuery = Quotation::query()
                    ->where('as_id', $user->id);
            }
        } elseif ($user->hasRole('Account Specialist')) {
            if ($isWeb) {
                $query->whereNot('assignment_status', 'ASSIGNED');
            } else {
                $query->where('as_id', $user->id);
            }

            $myQuotationsQuery = Quotation::query()
                ->where('as_id', $user->id);
        } elseif ($user->hasRole(['Operations', 'Client Success', 'Lead Operations', 'Lead Client Success'])) {
             // No additional constraints for these roles, they can see all quotations.
        } else {
            return null;
        }

        return [
            'query' => $query,
            'my_quotations_query' => $myQuotationsQuery,
        ];
    }

    private function buildQuotationQueryBuilder($query)
    {
        return QueryBuilder::for($query)->allowedFilters([
            AllowedFilter::exact('status'),
            AllowedFilter::callback('created_at', function ($query, $value) {
                $query->whereDate('created_at', $value);
            }),
            AllowedFilter::callback('assignment_status', function ($query, $value) {
                if ($value === 'ALL') {
                    return;
                }

                $query->where('assignment_status', $value);
            }),
            AllowedFilter::callback('service', function ($query, $value) {
                if ($value === 'LOGISTICS') {
                    $query->whereHas('logisticsService');
                } elseif ($value === 'REGULATORY') {
                    $query->whereHas('regulatoryService');
                } elseif ($value === 'ALL') {
                    return;
                }
            }),
        ]);
    }

    private function buildMyQuotationQueryBuilder($myQuotationsQuery)
    {
        if (!$myQuotationsQuery) {
            return null;
        }

        return QueryBuilder::for($myQuotationsQuery)->allowedFilters([
            AllowedFilter::exact('status'),
            AllowedFilter::callback('created_at', function ($query, $value) {
                $query->whereDate('created_at', $value);
            }),
            AllowedFilter::callback('assignment_status', function ($query, $value) {
                // Accept this filter key for shared request shape, but do not apply it to my_quotations.
                return;
            }),
            AllowedFilter::callback('service', function ($query, $value) {
                if ($value === 'LOGISTICS') {
                    $query->whereHas('logisticsService');
                } elseif ($value === 'REGULATORY') {
                    $query->whereHas('regulatoryService');
                } elseif ($value === 'ALL') {
                    return;
                }
            }),
        ]);
    }

    private function applyQueryConstraints($builder, $request, bool $applyAssignmentStatus = true): void
    {
        $status = $request->input('filter.status');
        if ($status) {
            $builder->where('status', $status);
        }

        $created_at = $request->input('filter.created_at');
        if ($created_at) {
            $builder->whereDate('created_at', $created_at);
        }

        $assignment_status = $request->input('filter.assignment_status');
        if ($applyAssignmentStatus && $assignment_status && $assignment_status !== 'ALL') {
            $builder->where('assignment_status', $assignment_status);
        }

        $service = $request->input('filter.service');
        if ($service === 'LOGISTICS') {
            $builder->whereHas('logisticsService');
        } elseif ($service === 'REGULATORY') {
            $builder->whereHas('regulatoryService');
        }

        if ($request->filled('client_id')) {
            $builder->where('client_id', $request->input('client_id'));
        }

        $this->applyClientTypeFilter($builder, $request);

        if ($request->search) {
            $this->applyMainSearchConstraint($builder, $request->search);
        }

        if ($request->has('as_search')) {
            $this->applyAsSearchConstraint($builder, $request->input('as_search'));
        }
    }

    private function applyClientTypeFilter($builder, $request): void
    {
        if (isset($request->client_type) && $request->client_type === 'OLD') {
            $oldClientIds = [];
            foreach (User::role('Client')->with('quotations')->get() as $client) {
                if ($client->quotations->count() > 1) {
                    $oldClientIds[] = $client->id;
                }
            }

            $builder->whereIn('client_id', $oldClientIds);
        } elseif (isset($request->client_type) && $request->client_type === 'NEW') {
            $newClientIds = [];
            foreach (User::role('Client')->with('quotations')->get() as $client) {
                if ($client->quotations->count() === 1) {
                    $newClientIds[] = $client->id;
                }
            }

            $builder->whereIn('client_id', $newClientIds);
        }
    }

    private function applyMainSearchConstraint($builder, string $search): void
    {
        $searchIds = (new Search())
            ->registerModel(Quotation::class, ['reference_number'])
            ->search($search)
            ->collect()
            ->pluck('searchable')
            ->map->id
            ->filter()
            ->values();

        $commoditySearchIds = Quotation::query()
            ->whereHas('logisticsService', function ($relationQuery) use ($search) {
                $relationQuery->where('commodity', 'like', "%{$search}%");
            })
            ->select('id')
            ->pluck('id');

        $clientSearchIds = Quotation::query()
            ->leftJoin('users as clients', 'quotations.client_id', '=', 'clients.id')
            ->where('clients.full_name', 'like', "%{$search}%")
            ->select('quotations.id')
            ->pluck('id');

        $mergedIds = $searchIds
            ->merge($commoditySearchIds)
            ->merge($clientSearchIds)
            ->unique()
            ->values();

        if ($mergedIds->isEmpty()) {
            $builder->whereRaw('1 = 0');
            return;
        }

        $builder->whereIn('id', $mergedIds);
    }

    private function applyAsSearchConstraint($builder, $asSearch): void
    {
        $asSearchIds = Quotation::query()
            ->leftJoin('users as specialists', 'quotations.as_id', '=', 'specialists.id')
            ->where('specialists.full_name', 'like', "%{$asSearch}%")
            ->select('quotations.id')
            ->pluck('id');

        if ($asSearchIds->isEmpty()) {
            $builder->whereRaw('1 = 0');
            return;
        }

        $builder->whereIn('id', $asSearchIds);
    }
}
