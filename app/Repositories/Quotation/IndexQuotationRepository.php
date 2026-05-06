<?php

namespace App\Repositories\Quotation;

use App\Http\Resources\QuotationResource;
use App\Models\IssuedQuotation;
use App\Models\Message;
use App\Models\Quotation;
use App\Models\User;
use App\Repositories\BaseRepository;
use Carbon\Carbon;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\Searchable\Search;

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
        $query = Quotation::query();
        $myQuotationsQuery = null;

        $allQuotationsCount = Quotation::count();

        $oldUsers = User::role('Client')->with('quotations')->get()->filter(function ($user) {
            return $user->quotations->count() > 1;
        })->pluck('id');
        $oldUserQuotationsCount = Quotation::whereIn('client_id', $oldUsers)->count();

        $newUsers = User::role('Client')->with('quotations')->get()->filter(function ($user) {
            return $user->quotations->count() === 1;
        })->pluck('id');
        $newUserQuotationsCount = Quotation::whereIn('client_id', $newUsers)->count();

        if ($user->hasRole('Client')) {
            $query->where('client_id', $user->id);
        } elseif ($user->hasRole('Lead Account Specialist')) {
            $query->whereNot('as_id', $user->id);
            $myQuotationsQuery = Quotation::query()->where('assignment_status', 'ASSIGNED')->where('as_id', $user->id);
        } elseif ($user->hasRole('Account Specialist')) {
            if ($isWeb) {
                $query->whereNot('assignment_status', 'ASSIGNED');
            } else {
                $query->where('as_id', $user->id);
            }
            $myQuotationsQuery = Quotation::query()->where('assignment_status', 'ASSIGNED')->where('as_id', $user->id);
        } else {
            return $this->error('Unauthorized', 403);
        }

        $quotations = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::callback('created_at', function ($query, $value) {
                    $query->whereDate('created_at', $value);
                }),
                AllowedFilter::callback('assignment_status', function ($query, $value) use ($user) {
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

        $myQuotations = $myQuotationsQuery
            ? QueryBuilder::for($myQuotationsQuery)->allowedFilters([
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
            ])
            : null;

        $applyQueryConstraints = function ($builder, bool $applyAssignmentStatus = true) use ($request) {
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
            if ($service) {
                if ($service === 'LOGISTICS') {
                    $builder->whereHas('logisticsService');
                } elseif ($service === 'REGULATORY') {
                    $builder->whereHas('regulatoryService');
                }
            }


            if ($request->filled('client_id')) {
                $builder->where('client_id', $request->input('client_id'));
            }

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

            if ($request->search) {
                $search = $request->search;
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

            if ($request->has('as_search')) {
                $asSearch = $request->input('as_search');
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
        };

        $applyQueryConstraints($quotations);
        if ($myQuotations) {
            $applyQueryConstraints($myQuotations, false);
        }

        $pagination = null;
        $myQuotationsPagination = null;

        if ($user->hasRole(['Account Specialist', 'Lead Account Specialist', 'Operations', 'Lead Operations'])) {
            if ($isWeb) {
                $formatQuotation = function ($quotation) use ($dateFormat) {
                    $issuedQuotation = IssuedQuotation::where('quotation_id', $quotation->id)->value('id');
                    
                    if ($quotation->accountSpecialist) {
                        $as = (mb_strtoupper($quotation->accountSpecialist?->username) . ' ' . $quotation->accountSpecialist?->full_name);
                    } else {
                        $as = null;
                    }

                    $quotationCard = Message::where('reference_id', $quotation->id)
                        ->where('type', 'QUOTATION_CARD')
                        ->first();
                    if ($quotationCard) {
                        $conversationId = $quotationCard->conversation_id;
                    }

                    if ($quotation->shipment) {
                        $shipmentCard = Message::where('reference_id', $quotation->shipment?->id)
                            ->where('type', 'SHIPMENT_CARD')
                            ->first();
                        if ($shipmentCard) {
                            $conversationId = $shipmentCard->conversation_id;
                        }
                    }

                    $clientType = $quotation->client->quotations()->count() > 1 ? 'OLD' : 'NEW';

                    $reassignmentRequest = $quotation->latestReassignmentRequest;
                    if ($reassignmentRequest && $reassignmentRequest->status !== 'PENDING') {
                        $reassignmentRequest = null;
                    }

                    return [
                        'id' => $quotation->id,
                        'reference_number' => $quotation->reference_number,
                        'date' => $quotation->created_at->format($dateFormat),
                        'client_full_name' => $quotation->client->full_name,
                        'client_type' => $clientType,
                        'status' => $quotation->status,
                        'assignment_status' => $quotation->assignment_status,
                        'account_specialist' =>  $as,
                        'as_profile_image' => $quotation->accountSpecialist->image_path ? asset($quotation->accountSpecialist?->image_path) : null,
                        'assigned_at' => $quotation->assigned_at ? Carbon::parse($quotation->assigned_at)->format($dateFormat) : null,
                        'reassignment_request_id' => $reassignmentRequest ? $reassignmentRequest->id : null,
                        'requested_at' => $reassignmentRequest ? Carbon::parse($reassignmentRequest->created_at)->format($dateFormat) : null,
                        'service' => $quotation->logisticsService ? 'LOGISTICS' : ($quotation->regulatoryService ? 'REGULATORY' : null),
                        'logistics_service' => $quotation->logisticsService ? [
                            'commodity' => $quotation->logisticsService->commodity,
                            'service_type' => $quotation->logisticsService->service_type,
                            'transport_mode' => $quotation->logisticsService->transport_mode,
                            'origin' => $quotation->logisticsService->origin,
                            'destination' => $quotation->logisticsService->destination,
                        ] : null,
                        'regulatory_service' => $quotation->regulatoryService ? [
                            'application_type' => $quotation->regulatoryService->application_type,
                        ] : null,
                        'conversation_id' => $conversationId ?? null,
                        'prepared_by' => $quotation->created_by ? User::where('id', $quotation->created_by)->value('full_name') : null,
                        'issued_quotation_id' => $issuedQuotation ?? null,
                    ];
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

                if ($quotations->isEmpty() && $myQuotationsResults->isEmpty()) {
                    return $this->success('No quotations found', [
                        'counts' => [
                            'all_quotations' => $allQuotationsCount,
                            'old_user_quotations' => $oldUserQuotationsCount,
                            'new_user_quotations' => $newUserQuotationsCount,
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
                        'old_user_quotations' => $oldUserQuotationsCount,
                        'new_user_quotations' => $newUserQuotationsCount,
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

                    $groupedByClient = $results->groupBy('client_id')->map(function ($clientQuotations) use ($dateFormat, $user) {
                        $client = $clientQuotations->first()->client;

                        return [
                            'client_id' => $client->id,
                            'client_full_name' => $client->full_name,
                            'quotations_count' => $clientQuotations->count(),
                            'date' => $clientQuotations->first()->created_at->format($dateFormat),
                            'quotations' => $clientQuotations->map(function ($quotation) use ($dateFormat, $user) {
                                $issuedQuotation = IssuedQuotation::where('quotation_id', $quotation->id)->value('id');
                                $quotationCard = Message::where('reference_id', $quotation->id)
                                    ->where('type', 'QUOTATION_CARD')
                                    ->first();
                                if ($quotationCard) {
                                    $conversationId = $quotationCard->conversation_id;
                                } else {
                                    $conversationId = null;
                                }

                                if ($quotation->shipment) {
                                    $shipmentCard = Message::where('reference_id', $quotation->shipment?->id)
                                        ->where('type', 'SHIPMENT_CARD')
                                        ->first();
                                    if ($shipmentCard) {
                                        $conversationId = $shipmentCard->conversation_id;
                                    }
                                }

                                if ($user->hasRole('Lead Account Specialist') && $user->id !== $quotation->as_id) {
                                    $conversationId = null;
                                }

                                $reassignmentRequest = $quotation->latestReassignmentRequest;
                                if ($reassignmentRequest && $reassignmentRequest->status !== 'PENDING') {
                                    $reassignmentRequest = null;
                                }

                                return [
                                    'id' => $quotation->id,
                                    'reference_number' => $quotation->reference_number,
                                    'date' => $quotation->created_at->format($dateFormat),
                                    'client_full_name' => $quotation->client->full_name,
                                    'status' => $quotation->status,
                                    'assignment_status' => $quotation->assignment_status,
                                    'as_username' => $quotation->accountSpecialist->username ?? 'Available',
                                    'as_full_name' => $quotation->accountSpecialist->full_name ?? null,
                                    'assigned_at' => $quotation->assigned_at ? Carbon::parse($quotation->assigned_at)->format($dateFormat) : null,
                                    'reassignment_request_id' => $reassignmentRequest ? $reassignmentRequest->id : null,
                                    'requested_at' => $reassignmentRequest ? Carbon::parse($reassignmentRequest->created_at)->format($dateFormat) : null,
                                    'service' => $quotation->logisticsService ? 'LOGISTICS' : ($quotation->regulatoryService ? 'REGULATORY' : null),
                                    'logistics_service' => $quotation->logisticsService ? [
                                        'commodity' => $quotation->logisticsService->commodity,
                                        'service_type' => $quotation->logisticsService->service_type,
                                        'transport_mode' => $quotation->logisticsService->transport_mode,
                                        'origin' => $quotation->logisticsService->origin,
                                        'destination' => $quotation->logisticsService->destination,
                                    ] : null,
                                    'regulatory_service' => $quotation->regulatoryService ? [
                                        'application_type' => $quotation->regulatoryService->application_type,
                                    ] : null,
                                    'conversation_id' => $conversationId ?? null,
                                    'prepared_by' => $quotation->created_by ? User::where('id', $quotation->created_by)->value('full_name') : null,
                                    'issued_quotation_id' => $issuedQuotation ?? null,
                                ];
                            })->values(),
                        ];
                    })->values();

                    if ($groupedByClient->isEmpty()) {
                        return $this->success('No quotations found', [], 200);
                    }

                    return $this->success('All quotations fetched', $groupedByClient, 200);
                } else {
                    $resultsQuery = $quotations->with(['client', 'logisticsService'])->orderBy('created_at', 'desc');

                    $results = $resultsQuery->get()->map(function ($result) use ($user, $request, $dateFormat) {
                        if ($request->has('filter.status')) {
                            $quotationCard = Message::where('reference_id', $result->id)
                                ->where('type', 'QUOTATION_CARD')
                                ->first();
                            if ($quotationCard) {
                                $conversationId = $quotationCard->conversation_id;
                            } else {
                                $conversationId = null;
                            }

                            if ($result->shipment) {
                                $shipmentCard = Message::where('reference_id', $result->shipment?->id)
                                    ->where('type', 'SHIPMENT_CARD')
                                    ->first();
                                if ($shipmentCard) {
                                    $conversationId = $shipmentCard->conversation_id;
                                }
                            }

                            if ($user->hasRole('Lead Account Specialist') && $user->id !== $result->as_id) {
                                $conversationId = null;
                            }

                            return [
                                'id' => $result->id,
                                'client_name' => $result->client->full_name,
                                'reference_number' => $result->reference_number,
                                'issued_quotation_id' => IssuedQuotation::where('quotation_id', $result->id)->value('id') ?? null,
                                'commodity' => $result->logisticsService?->commodity ?? $result->regulatoryService?->type_of_regulatory_assistance ?? null,
                                'date' => $result->created_at->format($dateFormat),
                                'conversation_id' => $conversationId ?? null,
                                'prepared_by' => $result->created_by ? User::where('id', $result->created_by)->value('full_name') : null,
                                'service' => $result->logisticsService ? 'LOGISTICS' : ($result->regulatoryService ? 'REGULATORY' : null),
                                'service_type' => $result->logisticsService ? $result->logisticsService->service_type : ($result->regulatoryService ? $result->regulatoryService->type_of_regulatory_assistance : null),
                                'reassignment_request_id' => $result->latestReassignmentRequest ? $result->latestReassignmentRequest->id : null,
                            ];
                        }
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

            $results = $results->map(function ($result) use ($user, $request, $dateFormat) {
                if ($request->has('filter.status')) {
                    $status = null;
                    if ($request->filter['status'] === 'RESPONDED') {
                        if ($result->status === 'RESPONDED') {
                            $status = 'NEW';
                        } else {
                            $status = $result->status;
                        }
                    }

                    $acceptedAt = null;
                    if ($result->status === 'ACCEPTED') {
                        $acceptedAt = $result->updated_at;
                    }

                    $quotationCard = Message::where('reference_id', $result->id)
                        ->where('type', 'QUOTATION_CARD')
                        ->first();
                    if ($quotationCard) {
                        $conversationId = $quotationCard->conversation_id;
                    }

                    if ($status === 'ACCEPTED') {
                        $shipmentCard = Message::where('reference_id', $shipment->id)
                            ->where('type', 'SHIPMENT_CARD')
                            ->first();
                        if ($shipmentCard) {
                            $conversationId = $shipmentCard->conversation_id;
                        }
                    }

                    return [
                        'id' => $result->id,
                        'reference_number' => $result->reference_number,
                        'commodity' => $result->logisticsService?->commodity ?? $result->regulatoryService?->type_of_regulatory_assistance ?? null,
                        'date' => $result->created_at->format($dateFormat),
                        'conversation_id' => $conversationId ?? null,
                        'reassignment_request_id' => $result->latestReassignmentRequest ? $result->latestReassignmentRequest->id : null,
                    ];
                }
            });

            return $this->success('All quotations fetched', $results->values(), 200);
        }
    }
}
