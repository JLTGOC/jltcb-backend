<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreQuotationRequest;
use App\Http\Requests\UpdateQuotationRequest;
use App\Http\Resources\ClientInputResource;
use App\Http\Resources\QuotationFileResource;
use App\Http\Resources\QuotationResource;
use App\Models\{
    LogisticsService,
    Quotation,
    User,
    ServiceOption,
    QuotationFile,
    Shipment,
    Message,
    QuotationTemplate,
    RegulatoryService,
    IssuedQuotation,
    BusinessType,
    RegulatoryAssistanceType,
    ContainerSize,
    ReassignmentRequest
};
use App\Services\QuotationFileService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Spatie\Searchable\Search;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class QuotationController extends Controller
{
    protected $quotationFileService;

    public function __construct(QuotationFileService $quotationFileService) {
        $this->authorizeResource(Quotation::class, 'quotation');
        $this->middleware('can:enumQuotationOptions,' . Quotation::class)->only('enumQuotationOptions');
        $this->middleware('can:upload,quotation')->only('upload');
        $this->middleware('can:showFile,quotation')->only('showFile');
        $this->middleware('can:acceptQuotation,quotation')->only('acceptQuotation');

        $this->quotationFileService = $quotationFileService;
    }

    /**
     * Index Quotations
     * 
     * Display a listing of the resource.
     */
    public function index(Request $request) {
        $user = auth()->user();
        $platform = strtolower((string) $request->header('Platform', 'mobile'));
        $isWeb = $platform === 'web';
        $perPage = $request->input('per_page', 10);
        $myPerPage = $request->input('my_per_page', $perPage);
        $dateFormat = $isWeb ? 'F d, Y' : 'Y/m/d';
        $query = Quotation::query();
        $myQuotationsQuery = null;

        if ($user->hasRole('Client')) {
            $query->where('client_id', $user->id);
        } elseif ($user->hasRole('Lead Account Specialist')) {
            // No additional query constraints needed.
        } elseif ($user->hasRole('Account Specialist')) {
            $myQuotationsQuery = Quotation::query()->where('as_id', $user->id);
        } else {
            return $this->error('Unauthorized', 403);
        }

        $request->validate([
            'filter.status' => 'required|in:REQUESTED,RESPONDED,ACCEPTED,DISCARDED',
            'client_type' => 'sometimes|in:OLD,NEW',
            'search' => 'sometimes|string',
            'page' => 'sometimes|integer|min:1',
            'my_page' => 'sometimes|integer|min:1',
            'client_id' => [
                'sometimes',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $isClient = User::role('Client')->where('id', $value)->exists();

                    if (!$isClient) {
                        $fail('The selected client must have a Client role.');
                    }
                },
            ],
        ]);

        $quotations = QueryBuilder::for($query)
            ->allowedFilters([AllowedFilter::exact('status')]);

        $myQuotations = $myQuotationsQuery
            ? QueryBuilder::for($myQuotationsQuery)->allowedFilters([AllowedFilter::exact('status')])
            : null;

        $applyQueryConstraints = function ($builder) use ($request) {
            $status = $request->input('filter.status');
            if ($status) {
                $builder->where('status', $status);
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
                    ->leftJoin('users', 'quotations.client_id', '=', 'users.id')
                    ->where('users.full_name', 'like', "%{$search}%")
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
        };

        $applyQueryConstraints($quotations);
        if ($myQuotations) {
            $applyQueryConstraints($myQuotations);
        }

        $pagination = null;
        $myQuotationsPagination = null;

        if ($user->hasRole('Account Specialist') || $user->hasRole('Lead Account Specialist')) {
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

                    return [
                        'id' => $quotation->id,
                        'reference_number' => $quotation->reference_number,
                        'date' => $quotation->created_at->format($dateFormat),
                        'client_full_name' => $quotation->client->full_name,
                        'status' => $quotation->status,
                        'assignment_status' => $quotation->assignment_status,
                        'account_specialist' =>  $as,
                        'assigned_at' => $quotation->assigned_at ? mb_strtoupper(Carbon::parse($quotation->assigned_at)->format($dateFormat)) : null,
                        'reassignment_request_id' => $quotation->latestReassignmentRequest ? $quotation->latestReassignmentRequest->id : null,
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

                if ($quotations->isEmpty()) {
                    return $this->success('No quotations found', [
                        'quotations' => [],
                        'my_quotations' => $myQuotationsResults,
                        'pagination' => $pagination,
                        'my_quotations_pagination' => $myQuotationsPagination,
                    ], 200);
                }

                return $this->success('All quotations fetched', [
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

                    $groupedByClient = $results->groupBy('client_id')->map(function ($clientQuotations) use ($dateFormat) {
                        $client = $clientQuotations->first()->client;

                        return [
                            'client_id' => $client->id,
                            'client_full_name' => $client->full_name,
                            'quotations_count' => $clientQuotations->count(),
                            'date' => $clientQuotations->first()->created_at->format($dateFormat),
                            'quotations' => $clientQuotations->map(function ($quotation) use ($dateFormat) {
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

                                return [
                                    'id' => $quotation->id,
                                    'reference_number' => $quotation->reference_number,
                                    'date' => $quotation->created_at->format($dateFormat),
                                    'client_full_name' => $quotation->client->full_name,
                                    'status' => $quotation->status,
                                    'assignment_status' => $quotation->assignment_status,
                                    'as_username' => $quotation->accountSpecialist->username ?? 'Available',
                                    'as_full_name' => $quotation->accountSpecialist->full_name ?? null,
                                    'assigned_at' => $quotation->assigned_at ? mb_strtoupper(Carbon::parse($quotation->assigned_at)->format($dateFormat)) : null,
                                    'reassignment_request_id' => $quotation->latestReassignmentRequest ? $quotation->latestReassignmentRequest->id : null,
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

            return $this->success('All quotations fetched', [
                'quotations' => $results->values(),
                'pagination' => $pagination,
            ], 200);
        }
    }

    /**
     * Store Quotation
     * 
     * Request new quotation
     */
    public function store(StoreQuotationRequest $request)
    {
        $user = User::find(auth()->id());

        DB::beginTransaction();

        try {
            $lastId = Quotation::max('id') ?? 0;
            $dateSection = Carbon::now()->format('m-Y');
            $idSection = str_pad($lastId+1, 3, '0', STR_PAD_LEFT);

            $previousQuotation = Quotation::where('client_id', $user->id)->latest()->first();
            if ($previousQuotation) {
                $assignedSpecialist = $previousQuotation->accountSpecialist;
            } 
            // else {
                // $specialists = User::role('Account Specialist')->get();
                // foreach ($specialists as $specialist) {
                //     $quotationsCount = Quotation::where('as_id', $specialist->id)->count();
                //     $specialist->quotations_count = $quotationsCount;
                // }

                // $minCount = $specialists->min('quotations_count');

                // if ($specialists->where('quotations_count', $minCount)->count() > 1) {
                //     foreach ($specialists->where('quotations_count', $minCount) as $specialist) {
                //         $specialist->lastest_quotation = Quotation::where('as_id', $specialist->id)->latest()->first()?->created_at ?? Carbon::createFromTimestamp(0);
                //     }
                //     $assignedSpecialist = $specialists->where('quotations_count', $minCount)->sortBy('lastest_quotation')->first();
                // } else {
                //     $assignedSpecialist = $specialists->where('quotations_count', $minCount)->first();
                // }
            // }

            if ($assignedSpecialist) {
                $assignmentStatus = 'ASSIGNED';
                $assignedAt = Carbon::now();
            }

            $quotation = Quotation::create([
                'reference_number' => "QT-{$dateSection}-{$idSection}",
                'client_id' => $user->id,
                'as_id' => $assignedSpecialist->id ?? null,
                'company_name' => $request->input('company.name'),
                'company_address' => $request->input('company.address'),
                'contact_person' => $request->input('company.contact_person'),
                'contact_number' => $request->input('company.contact_number'),
                'email' => $request->input('company.email'),
                'position' => $request->input('company.position'),
                'assignment_status' => $assignmentStatus ?? 'UNASSIGNED',
                'assigned_at' => $assignedAt ?? null
            ]);

            if ($request->input('services') === 'LOGISTICS') {
                $stringifiedServiceOptions = implode(',', $request->service['options']);
                $specialists = User::role('Account Specialist')->pluck('id');

                $logisticsService = $quotation->logisticsService()->create([
                    'service_type' => $request->service['type'],
                    'transport_mode' => $request->service['transport_mode'],
                    'service_options' => $stringifiedServiceOptions,
                    'commodity' => $request->commodity['commodity'],
                    'cargo_type' => $request->commodity['cargo_type'],
                    'container_size' => $request->commodity['container_size'] ?? null,
                    'origin' => $request->shipment['origin'],
                    'destination' => $request->shipment['destination'],
                    'remarks' => $request->remarks ?? null,
                ]);

                if ($quotation->cargo_type === 'LCL' && isset($quotation->container_size)) {
                    $quotation->update([
                        'container_size' => null
                    ]);
                }
            } elseif ($request->input('services') === 'REGULATORY') {
                $typeOfRegulatoryAssistance = implode(',', $request->type_of_regulatory_assistance);

                $regulatoryService = $quotation->regulatoryService()->create([
                    'full_name' => $request->input('full_name'),
                    'contact_person_contact_number' => $request->input('company.cp_contact_number'),
                    'business_type' => $request->input('company.business_type'),
                    'position' => $request->input('company.position'),
                    'type_of_regulatory_assistance' => $typeOfRegulatoryAssistance,
                    'application_type' => $request->service_level,
                    'message' => $request->message ?? null,
                ]);
            }

            // Upload client documents
            $fileUploaded = $this->quotationFileService->syncClientDocuments(
                $quotation, $user, newFiles: $request->file('documents')
            );

            if ($fileUploaded !== true) {
                return $this->error($fileUploaded->getMessage());
            }

            DB::commit();

            return $this->success('Quotation request submitted', new QuotationResource($quotation), 200);
        } catch (ValidationException $e) {
            DB::rollback();
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            DB::rollback();
            return $this->error('Something went wrong', 400, $e->getMessage());
        }
    }

    /**
     * Show Quotation
     * 
     * Show individual quotation details
     */
    public function show(Quotation $quotation) {   
        if (!$quotation) {
            return $this->error('Quotation not found', 404);
        }

        $quotationCollection = new QuotationResource($quotation);

        return $this->success('Quotation details fetched successfully', $quotationCollection, 200);
    }

    /**
     * Update Quotation
     * 
     * Update quotation request details
     */
    public function update(UpdateQuotationRequest $request, Quotation $quotation)
    {
        $user = User::find(auth()->id());

        if (((int) $user->id === (int) $quotation->client_id) || ((int) $user->id === (int) $quotation->as_id) || $user->hasRole('Lead Account Specialist')) {
            try {
                DB::beginTransaction();

                $accepted = $quotation->status === 'ACCEPTED';
                $shipped = Shipment::where('quotation_id', $quotation->id)->first();
                if ($accepted) {
                    return $this->error('Quotation already accepted', 422);
                }
                if ($shipped) {
                    return $this->error('Shipment already ongoing', 422);
                }

                $serviceType = $request->input('services');

                if (!$serviceType) {
                    if ($quotation->logisticsService) {
                        $serviceType = 'LOGISTICS';
                    } elseif ($quotation->regulatoryService) {
                        $serviceType = 'REGULATORY';
                    } elseif ($request->hasAny(['company.business_type', 'business_type', 'type_of_regulatory_assistance', 'service_level', 'message'])) {
                        $serviceType = 'REGULATORY';
                    } elseif ($request->hasAny(['service', 'commodity', 'shipment', 'remarks'])) {
                        $serviceType = 'LOGISTICS';
                    }
                }

                $quotation->update([
                    'company_name' => $request->input('company.name', $quotation->company_name),
                    'company_address' => $request->input('company.address', $quotation->company_address),
                    'contact_person' => $request->input('company.contact_person', $quotation->contact_person),
                    'contact_number' => $request->input('company.contact_number', $quotation->contact_number),
                    'email' => $request->input('company.email', $quotation->email),
                ]);

                if ($serviceType === 'REGULATORY') {
                    $typeOfRegulatoryAssistance = $request->has('type_of_regulatory_assistance')
                        ? implode(',', $request->input('type_of_regulatory_assistance', []))
                        : $quotation->regulatoryService?->type_of_regulatory_assistance;

                    $quotation->regulatoryService()->updateOrCreate(
                        ['quotation_id' => $quotation->id],
                        [
                            'business_type' => $request->input('company.business_type', $quotation->regulatoryService?->business_type),
                            'type_of_regulatory_assistance' => $typeOfRegulatoryAssistance,
                            'application_type' => $request->input('service_level', $quotation->regulatoryService?->application_type),
                            'message' => $request->input('message', $quotation->regulatoryService?->message),
                        ]
                    );

                    if ($quotation->logisticsService) {
                        $quotation->logisticsService()->delete();
                    }

                } elseif ($serviceType === 'LOGISTICS') {
                    $serviceOptions = $request->has('service.options')
                        ? implode(',', $request->input('service.options', []))
                        : $quotation->logisticsService?->service_options;

                    $incomingCargoType = $request->input('commodity.cargo_type', $quotation->logisticsService?->cargo_type);
                    $containerSize = $request->input('commodity.container_size', $quotation->logisticsService?->container_size);
                    if ($incomingCargoType === 'LCL') {
                        $containerSize = null;
                    }

                    $quotation->logisticsService()->updateOrCreate(
                        ['quotation_id' => $quotation->id],
                        [
                            'service_type' => $request->input('service.type', $quotation->logisticsService?->service_type),
                            'transport_mode' => $request->input('service.transport_mode', $quotation->logisticsService?->transport_mode),
                            'service_options' => $serviceOptions,
                            'commodity' => $request->input('commodity.commodity', $quotation->logisticsService?->commodity),
                            'cargo_type' => $incomingCargoType,
                            'container_size' => $containerSize,
                            'origin' => $request->input('shipment.origin', $quotation->logisticsService?->origin),
                            'destination' => $request->input('shipment.destination', $quotation->logisticsService?->destination),
                            'remarks' => $request->input('remarks', $quotation->logisticsService?->remarks),
                        ]
                    );

                    if ($quotation->regulatoryService) {
                        $quotation->regulatoryService()->delete();
                    }
                }

                 // Re-upload client documents
                $fileUploaded = $this->quotationFileService->syncClientDocuments(
                    $quotation, 
                    $user,
                    newFiles: $request->file('documents', []), 
                    removedFileIds: $request->input('removed_documents', []) 
                );

                if ($fileUploaded !== true) {
                    return $this->error($fileUploaded->getMessage());
                }

                DB::commit();

                return $this->success('Quotation request updated', new QuotationResource($quotation), 200);

            } catch (ValidationException $e) {
                DB::rollback();
                return $this->error('Validation failed', 422, $e->errors());
            } catch (\Exception $e) {
                DB::rollback();
                return $this->error('Something went wrong', 400, $e->getMessage());
            }
        } else {
            return $this->error('Unauthorized', 403);
        }
    }

    /**
     * Destroy Quotation
     * 
     * Remove the specified resource from storage.
     */
    public function destroy(Quotation $quotation)
    {
        $quotation->delete();

        return $this->success('Quotation deleted', [], 200);
    }

    /**
     * Enum Quotation Options
     * 
     * Fetch enumeration options for quotations
     */
    public function enumQuotationOptions() {
        $user = auth()->user();
        $autofillDetails = [
            'full_name' => $user->full_name,
            'company' => [
                'name' => $user->company_name,
                'address' => $user->company_address,
                'position' => $user->company_position,
                'contact_number' => $user->contact_number,
                'email' => $user->email,
                'business_type' => $user->business_type,
            ],
        ];
        $businessTypes = BusinessType::pluck('name');
        $regulatoryAssistanceTypes = RegulatoryAssistanceType::pluck('name');
        $serviceTypes = ['IMPORT', 'EXPORT'];
        $transportModes = ['AIR', 'SEA'];
        $serviceOptions = ServiceOption::where('status', 'ENABLED')->pluck('name');
        $cargoType = ['CONTAINERIZED', 'LCL'];
        $containerSize = ContainerSize::pluck('size');

        $quotationOptions = [
            'autofill_details' => $autofillDetails,
            'business_types' => $businessTypes,
            'regulatory_assistance_types' => $regulatoryAssistanceTypes,
            'service_types' => $serviceTypes,
            'transport_modes' => $transportModes,
            'service_options' => $serviceOptions,
            'cargo_type' => $cargoType,
            'container_size' => $containerSize,
        ];

        return $this->success('Quotation options fetched', $quotationOptions, 200);
    }

    /**
     * Upload Quotation File
     * 
     * Uploads a file for the quotation
     */
    public function upload(Quotation $quotation, Request $request) {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,xls,xlsx']
        ]);

        DB::beginTransaction();
        try {

            $quotationFile = $this->quotationFileService->uploadQuotationFile(
                $quotation, $request->file('file'), $request->user()
            );
            
            $message = $quotationFile->wasRecentlyCreated 
                ? 'Quotation file uploaded successfully' 
                : 'Quotation file updated sucessfully';

            $status = $quotationFile->wasRecentlyCreated ? 201 : 200;

            DB::commit();
            return $this->success($message, new QuotationFileResource($quotationFile), $status);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(
                'Failed to upload quotation file', 500, $e->getMessage()
            );
        }
    } 

    /**
     * Upload Client Documents
     * 
     * Upload files for client documents
     */
    private function uploadClientDocuments(
        Quotation $quotation,
        User $user,
        array $newFiles = [],
        array $removedFileIds = []
    ) {
        $type = 'REQUESTED';

        DB::beginTransaction();
        try {
            // Delete only the files explicitly marked for removal
            if (!empty($removedFileIds)) {
                $filesToRemove = $quotation->files()->whereIn('id', $removedFileIds)->get();
                foreach ($filesToRemove as $file) {
                    Storage::disk('local')->delete($file->file_path);
                    $file->delete();
                }
            }

            // Upload new files if provided
            if (!empty($newFiles)) {
                foreach ($newFiles as $file) {
                    $filename = $file->hashName();
                    $path = $file->storeAs('files', $filename, 'local');
                    $originalFileName = $file->getClientOriginalName();

                    QuotationFile::create([
                        'quotation_id' => $quotation->id,
                        'file_path' => $path,
                        'original_file_name' => $originalFileName,
                        'uploaded_by' => $user->id,
                        'type' => $type,
                        'file_type' => $file->getClientOriginalExtension()
                    ]);
                }
            }

            DB::commit();
            return true; // success
        } catch (\Exception $e) {
            DB::rollBack();
            return $e; // return exception for error handling
        }
    }

    /**
     * Reassign Account Specialist
     * 
     * Allows Lead Account Specialist to reassign the Account Specialist in charge of a quotation
     */
    public function reassignSpecialist(Quotation $quotation, Request $request) {
        $this->authorize('reassignSpecialist', $quotation);

        if (!$quotation) {
            return $this->error('Quotation not found', 404);
        }

        $reassignmentRequest = ReassignmentRequest::where('quotation_id', $quotation->id)->where('status', 'PENDING')->latest()->first();

        if (!$reassignmentRequest) {
            return $this->error('No pending reassignment request for this quotation', 422);
        }

        $request->validate([
            'status' => ['required', 'in:APPROVED,REJECTED'],
            'as_id' => ['required_if:status,APPROVED', 'integer', 'exists:users,id']
        ]);

        if ($request->status === 'REJECTED') {
            $quotation->update([
                'assignment_status' => 'ASSIGNED',
            ]);

            $reassignmentRequest->update([
                'status' => 'REJECTED'
            ]);

            return $this->success('Reassignment request rejected, previous Account Specialist retained', $reassignmentRequest, 200);
        } elseif ($request->status === 'APPROVED') {
            $user = User::find($request->as_id);

            if (!$user->hasRole('Account Specialist')) {
                return $this->error('The selected user must have an Account Specialist role.', 422);
            }
            if ((int) $request->as_id === $quotation->as_id) {
                return $this->error('The selected Account Specialist is already assigned to this quotation.', 422);
            }

            $quotation->update([
                'as_id' => $request->as_id,
                'assignment_status' => 'ASSIGNED',
                'assigned_at' => Carbon::now()
            ]);

            $reassignmentRequest->update([
                'status' => 'APPROVED'
            ]);

            return $this->success('Account Specialist reassigned successfully', new QuotationResource($quotation), 200);
        }
    }

    /**
     * Request Reassignment
     * 
     * Allows Account Specialist to request for reassignment of a quotation to another Account Specialist, changing the assignment status to REASSIGNMENT REQUESTED
     */
    public function requestReassignment(Quotation $quotation, Request $request) {
        $this->authorize('requestReassignment', $quotation);

        if (auth()->user()->id !== $quotation->as_id) {
            return $this->error('Only the assigned Account Specialist can request for reassignment', 403);
        }

        $reassignmentRequest = ReassignmentRequest::where('quotation_id', $quotation->id)->where('status', 'PENDING')->latest()->first();

        if ($reassignmentRequest) {
            return $this->error('A reassignment request is already pending for this quotation', 422);
        } else {
            $request->validate([
                'reason' => ['required', 'string', 'in:WORKLOAD,EMERGENCY / LEAVE,CLIENT REQUEST'],
                'additional_details' => ['nullable', 'string']
            ]);

            $quotation->update([
                'assignment_status' => 'REASSIGNMENT REQUESTED',
            ]);

            $reassignmentRequest = ReassignmentRequest::create([
                'quotation_id' => $quotation->id,
                'as_id' => auth()->id(),
                'reason' => $request->reason,
                'additional_details' => $request->additional_details,
                'status' => 'PENDING'
            ]);

            return $this->success('Reassignment request submitted successfully', $reassignmentRequest, 200);
        }
    }

    /**
     * Accept Quotation
     * 
     * Allows Client to accept a quotation, changing its status to ACCEPTED
     */
    public function acceptQuotation(Quotation $quotation, Request $request) {
        $quotation->update([
            'status' => 'ACCEPTED',
            'assignment_status' => 'ASSIGNED',
            'assigned_at' => Carbon::now(),
        ]);

        return $this->success('Quotation accepted successfully', new QuotationResource($quotation), 200);
    }

    /**
     * Show Client inputs
     * 
     * Show specific client quotation details based on quotation template configured
     */
    public function clientInputs(Quotation $quotation, Request $request) {
        $request->validate([
            'template_id' => [
            'required',
            'integer',
            'exists:quotation_templates,id',
            function ($attribute, $value, $fail) use ($quotation) {
                if ($quotation->regulatoryService) {
                    $type = 'REGULATORY';
                } elseif ($quotation->logisticsService) {
                    $type = 'LOGISTICS';
                } else {
                    return;
                }

                $template = QuotationTemplate::find($value);

                if (!$template) {
                    return; 
                }

                $quotationField = $template->quotationFields()->first();

                if (!$quotationField || $quotationField->quotation_type !== $type) {
                    $fail('The template id is not compatible with this quotation');
                }
            }
            ],
        ]);

        $template = QuotationTemplate::find($request->template_id);

        return $this->success(
            'Template based client inputs fetched successfully',
            new ClientInputResource($template, $quotation->id)
        );
    }
}
