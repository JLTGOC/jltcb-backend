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
};
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

    public function __construct() {
        $this->authorizeResource(Quotation::class, 'quotation');
        $this->middleware('can:enumQuotationOptions,' . Quotation::class)->only('enumQuotationOptions');
        $this->middleware('can:upload,quotation')->only('upload');
        $this->middleware('can:showFile,quotation')->only('showFile');
        $this->middleware('can:reassignSpecialist,quotation')->only('reassignSpecialist');
        $this->middleware('can:acceptQuotation,quotation')->only('acceptQuotation');
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
        $perPage = $request->input('perPage', 10);
        $selectedClientId = $request->input('client_id');
        $dateFormat = $isWeb ? 'm/d/y' : 'Y/m/d';
        $query = Quotation::query();
        if ($user->hasRole('Client')) {
            $query->where('client_id', $user->id);
        } elseif ($user->hasRole('Account Specialist')) {
            $query->where('as_id', $user->id);
        } elseif ($user->hasRole('Lead Account Specialist')) {
            // No additional query constraints for Lead Account Specialist
        } else {
            return $this->error('Unauthorized', 403);
        }

        $request->validate([
            'filter.status' => 'required|in:REQUESTED,RESPONDED,ACCEPTED,DISCARDED',
            'search' => 'sometimes|string',
            'perPage' => 'sometimes|integer|min:1|max:100',
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

        $status = $request->input('filter.status');
        if ($status) {
            $quotations->where('status', $status);
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
                return $this->success('No quotations found', [], 200);
            }

            $quotations->whereIn('id', $mergedIds);
        }

        $pagination = null;

        if (($user->hasRole('Account Specialist') || $user->hasRole('Lead Account Specialist')) && $request->filter['status'] === 'REQUESTED') {
            if ($isWeb) {
                if ($selectedClientId) {
                    $resultsQuery = (clone $quotations)
                        ->with(['client', 'accountSpecialist'])
                        ->where('client_id', $selectedClientId)
                        ->orderBy('created_at', 'desc');

                    $paginated = $resultsQuery->paginate($perPage);
                    $pagination = $this->pagePaginationData($paginated);

                    $quotationsForClient = $paginated->getCollection()->map(function ($quotation) use ($dateFormat) {
                        $card = Message::where('reference_id', $quotation->id)
                            ->where('type', 'QUOTATION_CARD')
                            ->first();
                        if ($card) {
                            $conversationId = $card->conversation_id;
                        }

                        return [
                            'id' => $quotation->id,
                            'date' => $quotation->created_at->format($dateFormat),
                            'person_in_charge' => $quotation->accountSpecialist->full_name,
                            'commodity' => $quotation->logisticsService?->commodity ?? null,
                            'service_type' => $quotation->logisticsService?->service_type ?? null,
                            'conversation_id' => $conversationId ?? null,
                            'prepared_by' => $quotation->created_by ? User::where('id', $quotation->created_by)->value('full_name') : null,
                        ];
                    })->values();

                    $clientName = $paginated->getCollection()->first()?->client?->full_name
                        ?? User::where('id', $selectedClientId)->value('full_name');

                    $results = [[
                        'client_id' => $selectedClientId,
                        'name' => $clientName,
                        'request_count' => $paginated->total(),
                        'quotations' => $quotationsForClient,
                    ]];

                    return $this->success('All quotations fetched', [
                        'quotations' => $results,
                        'pagination' => $pagination,
                    ], 200);
                }

                $paginatedClients = (clone $quotations)
                    ->select('client_id', DB::raw('MAX(created_at) as latest_created_at'))
                    ->groupBy('client_id')
                    ->orderBy('latest_created_at', 'desc')
                    ->paginate($perPage);

                $pagination = $this->pagePaginationData($paginatedClients);
                $clientIds = $paginatedClients->getCollection()->pluck('client_id')->values();

                if ($clientIds->isEmpty()) {
                    $results = collect();
                } else {
                    $groupedByClient = (clone $quotations)
                        ->with(['client', 'accountSpecialist'])
                        ->whereIn('client_id', $clientIds)
                        ->orderBy('created_at', 'desc')
                        ->get()
                        ->groupBy('client_id');

                    $results = $clientIds->map(function ($clientId) use ($groupedByClient, $dateFormat) {
                        $userQuotations = $groupedByClient->get($clientId, collect());

                        if ($userQuotations->isEmpty()) {
                            return null;
                        }

                        $firstQuotation = $userQuotations->first();

                        return [
                            'client_id' => $firstQuotation->client_id,
                            'name' => $firstQuotation->client->full_name,
                            'request_count' => $userQuotations->count(),
                            'quotations' => $userQuotations->map(function ($quotation) use ($dateFormat) {
                                $card = Message::where('reference_id', $quotation->id)
                                    ->where('type', 'QUOTATION_CARD')
                                    ->first();
                                if ($card) {
                                    $conversationId = $card->conversation_id;
                                }

                                return [
                                    'id' => $quotation->id,
                                    'date' => $quotation->created_at->format($dateFormat),
                                    'person_in_charge' => $quotation->accountSpecialist->full_name,
                                    'commodity' => $quotation->logisticsService?->commodity ?? null,
                                    'service_type' => $quotation->logisticsService?->service_type ?? null,
                                    'conversation_id' => $conversationId ?? null,
                                    'prepared_by' => $quotation->created_by ? User::where('id', $quotation->created_by)->value('full_name') : null,
                                ];
                            })->values(),
                        ];
                    })->filter()->values();
                }

                return $this->success('All quotations fetched', [
                    'quotations' => $results,
                    'pagination' => $pagination,
                ], 200);
            } else {
                $resultsQuery = (clone $quotations)->with('client')->orderBy('created_at', 'desc');
                $results = $resultsQuery->get();

                $results = $results->groupBy('client_id')->map(function ($userQuotations) use ($dateFormat) {
                    $firstQuotation = $userQuotations->first();
                    // $client = User::where('id', $firstQuotation->client_id)->value('full_name');

                    return [
                        'client_id' => $firstQuotation->client_id,
                        'name' => $firstQuotation->client->full_name,
                        'request_count' => $userQuotations->count(),
                        'quotations' => $userQuotations->map(function ($quotation) use ($dateFormat) {
                            $card = Message::where('reference_id', $quotation->id)
                                ->where('type', 'QUOTATION_CARD')
                                ->first();
                            if ($card) {
                                $conversationId = $card->conversation_id;
                            }

                            return [
                                'id' => $quotation->id,
                                'date' => $quotation->created_at->format($dateFormat),
                                'person_in_charge' => $quotation->accountSpecialist->full_name,
                                'commodity' => $quotation->logisticsService?->commodity ?? null,
                                'service_type' => $quotation->logisticsService?->service_type ?? null,
                                'conversation_id' => $conversationId ?? null,
                                'prepared_by' => $quotation->created_by ? User::where('id', $quotation->created_by)->value('full_name') : null,

                            ];
                        })->values(),
                    ];
                })->values();
            }
        } else {
            $resultsQuery = $quotations->with('client')->orderBy('created_at', 'desc');

            if ($isWeb) {
                $paginated = $resultsQuery->paginate($perPage);
                $pagination = $this->pagePaginationData($paginated);
                $results = $paginated->getCollection();
            } else {
                $results = $resultsQuery->get();
            }

            $results = $results->map(function ($result) use ($user,$request, $dateFormat) {
                if ($request->has('filter.status')) {
                    $status = null;

                    if ($user->hasRole('Client') && $request->filter['status'] === 'RESPONDED') {
                        $status = 'NEW';
                        
                        $shipment = Shipment::where('quotation_id', $result->id)->first();

                        if ($shipment) {
                            $status = 'ACCEPTED';
                        }
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
                        'client_name' => $result->client->full_name,
                        'reference_number' => $result->reference_number,
                        'commodity' => $result->logisticsService?->commodity ?? null,
                        'service_type' => $result->logisticsService?->service_type ?? null,
                        'date' => $result->created_at->format($dateFormat),
                        'status' => $status ?? $result->status,
                        'conversation_id' => $conversationId ?? null,
                        'prepared_by' => $result->created_by ? User::where('id', $result->created_by)->value('full_name') : null,
                    ];
                }
            });
        }

        if ($results->isEmpty()) {
            return $this->success('No quotations found', [], 200);
        }

        if ($isWeb) {
            return $this->success('All quotations fetched', [
                'quotations' => $results->values(),
                'pagination' => $pagination,
            ], 200);
        }

        return $this->success('All quotations fetched', $results->values(), 200);
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
            } else {
                $specialists = User::role('Account Specialist')->get();
                foreach ($specialists as $specialist) {
                    $quotationsCount = Quotation::where('as_id', $specialist->id)->count();
                    $specialist->quotations_count = $quotationsCount;
                }

                $minCount = $specialists->min('quotations_count');

                if ($specialists->where('quotations_count', $minCount)->count() > 1) {
                    foreach ($specialists->where('quotations_count', $minCount) as $specialist) {
                        $specialist->lastest_quotation = Quotation::where('as_id', $specialist->id)->latest()->first()?->created_at ?? Carbon::createFromTimestamp(0);
                    }
                    $assignedSpecialist = $specialists->where('quotations_count', $minCount)->sortBy('lastest_quotation')->first();
                } else {
                    $assignedSpecialist = $specialists->where('quotations_count', $minCount)->first();
                }
            }

            $quotation = Quotation::create([
                'reference_number' => "QT-{$dateSection}-{$idSection}",
                'client_id' => $user->id,
                'as_id' => $assignedSpecialist->id,
                'company_name' => $request->input('company.name'),
                'company_address' => $request->input('company.address'),
                'contact_person' => $request->input('company.contact_person'),
                'contact_number' => $request->input('company.contact_number'),
                'email' => $request->input('company.email'),
                'position' => $request->input('company.position'),
            ]);

            // if ($request->input('services') === 'LOGISTICS') {
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

            // } elseif ($request->input('services') === 'REGULATORY') {
            //     $typeOfRegulatoryAssistance = implode(',', $request->type_of_regulatory_assistance);

            //     $regulatoryService = $quotation->regulatoryService()->create([
            //         'business_type' => $request->input('company.business_type'),
            //         'type_of_regulatory_assistance' => $typeOfRegulatoryAssistance,
            //         'application_type' => $request->service_level,
            //         'message' => $request->message ?? null,
            //     ]);
            // }

            // Upload client documents
            $newFiles = $request->file('documents');

            $fileUploaded = $this->uploadClientDocuments(
                $quotation,
                $request->user(), 
                $newFiles =  $newFiles
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

                // $serviceType = $request->input('services');
                $serviceType = 'LOGISTICS';
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
                $removedFileIds = $request->input('removed_documents', []);
                $newFiles = $request->file('documents', []);

                $fileUploaded = $this->uploadClientDocuments(
                    $quotation,
                    $request->user(), 
                    $newFiles = $newFiles, 
                    $removedFileIds = $removedFileIds
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
        $businessTypes = ['COOPERATIVE', 'CORPORATION', 'E-COMMERCE', 'INDIVIDUAL IMPORTER', 'GOVERNMENT AGENCY', 'IMPORT-EXPORT AGENT', 'MULTINATIONAL COMPANY', 'NON-PROFIT ORGANIZATION', 'PARTNERSHIP', 'PEZA-REGISTERED ENTERPRISE', 'SOLE PROPRIETORSHIP'];
        $regulatoryAssistanceTypes = [
            'BEAUREAU OF CUSTOMS (BOC)',
            'PHILIPINE EXPORTERS CONFEDERATION, INC. (PHILEXPORT)',
            'PHILIPPINE ECONOMIC ZONE AUTHORITY (PEZA)',
            'DEPARTMENT OF FINANCE (DOF)',
            'FOOD AND DRUG ADMINISTRATION (FDA)',
            'BEAUREAU OF INTERNAL REVENUE (BIR)',
            'BEAUREAU OF ANIMAL INDUSTRY (BAI)',
            'NATIONAL MEAT INSPECTION SERVICE (NMIS)',
            'BEAUREAU OF FISHIERIES AND AQUATIC RESOURCES (BFAR)',
            'BEAUREAU OF AGRICULTURE AND FISHERIES STANDARDS (BAFS)',
            'NATIONAL TELECOMMUNICATIONS COMMISSION (NTC)',
            'OPTICAL MEDIA BOARD (OMB)',
            'DEPARTMENT OF TRADE AND INDUSTRY - BEAUREAU OF PRODUCT STANDARDS (DTI-BPS)',
            'SUGAR REGULATORY ADMINISTRATION (SRA)',
            'DANGEROUS DRUGS BOARD (DDB)',
            'THE PHILIPPINE DRUG ENFORCEMENT ADMINISTRATION (PDEA)',
        ];
        $serviceTypes = ['IMPORT', 'EXPORT', 'BUSINESS SOLUTION'];
        $transportModes = ['AIR', 'SEA'];
        $serviceOptions = ServiceOption::pluck('name');
        $cargoType = ['CONTAINERIZED', 'LCL'];
        $containerSize = ['1x10', '1x20', '1x40'];

        $quotationOptions = [
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

        $user = $request->user();

        $file = $request->file('file');
        $directory = 'files';
        $originalFileName = $file->getClientOriginalName();
        $type = 'PROPOSAL';
        $fileType = $file->getClientOriginalExtension();
  

        $existingFile = $quotation->files()->where('type', $type)->first();

        if ($existingFile) {
            $existingFileName = str_replace('/files/', '', $existingFile->file_path);
            $filename = $existingFileName;
        } else {
            $filename = $file->hashName();
        }
       
        DB::beginTransaction();
        try {

            $path = $file->storeAs($directory, $filename, 'public');

            $quotationFile = QuotationFile::updateOrCreate(
                [
                    'quotation_id' => $quotation->id,
                    'uploaded_by' => $user->id
                ],
                [
                    'file_path' => $path,
                    'type' => $type,
                    'original_file_name' => $originalFileName,
                    'file_type' => $fileType
                ],
            );

            $quotation->update([
                'status' => 'RESPONDED',
                'created_by' => $user->id
            ]);
            
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
                    Storage::disk('public')->delete($file->file_path);
                    $file->delete();
                }
            }

            // Upload new files if provided
            if (!empty($newFiles)) {
                foreach ($newFiles as $file) {
                    $filename = $file->hashName();
                    $path = $file->storeAs('files', $filename, 'public');
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
        $request->validate([
            'as_id' => ['required', 'integer', 'exists:users,id']
        ]);

        $user = User::find($request->as_id);

        if (!$user->hasRole('Account Specialist')) {
            return $this->error('The selected user must have an Account Specialist role.', 422);
        }

        $quotation->update([
            'as_id' => $request->as_id
        ]);

        return $this->success('Account Specialist reassigned successfully', new QuotationResource($quotation), 200);
    }

    /**
     * Accept Quotation
     * 
     * Allows Client to accept a quotation, changing its status to ACCEPTED
     */
    public function acceptQuotation(Quotation $quotation, Request $request) {
        $quotation->update([
            'status' => 'ACCEPTED'
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
