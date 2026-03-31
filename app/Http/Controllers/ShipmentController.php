<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{
    Shipment,
    Quotation,
    QuotationFile,
    ShipmentFile,
    Conversation,
    User,
    JobOrder,
};
use App\Http\Resources\ShipmentResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\Searchable\Search;

class ShipmentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Shipment::class, 'shipment', [
            'except' => ['store'],
        ]);
    }

    /**
     * Index Shipments
     * 
     * Fetch all shipments.
     */     
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $search = $request->input('search');    
        $platform = $request->header('Platform', 'mobile');

        if ($platform === 'mobile') {
            $request->validate([
                'filter.status' => 'required|in:ONGOING,DELIVERED'
            ]);
        }

        $user = $request->user();

        // Allows conditional base query depending on role
        $baseQuery = $user->hasRole('Client') ? Shipment::where('client_id', $user->id) : Shipment::class;

        $shipmentsQuery = QueryBuilder::for($baseQuery)
            ->allowedFilters(AllowedFilter::exact('status'))
            ->defaultSort('-created_at', '-id');

        if ($search) {
            if ($platform === 'mobile') {
                // Search query for mobile
                $shipmentsQuery->where('reference_number', 'LIKE', '%' . $search . '%');
            } else {
                // Search query for web
                $searchResults = (new Search())
                    ->registerModel(Shipment::class, [
                        'reference_number',
                        'status',
                        'commodity',
                    ])
                    ->search($search)
                    ->pluck('searchable.id'); // Get only shipment ids

                $shipmentsQuery->whereIn('id', $searchResults);
            }
        } 

        // Normal pagination for web platform
        if ($platform === 'web') {
            $paginated = $shipmentsQuery->paginate($perPage);
            $pagination = $this->pagePaginationData($paginated);
        } else {
            // Cursor pagination for mobile platform
            $paginated = $shipmentsQuery->cursorPaginate($perPage);
            $pagination = $this->cursorPaginationData($paginated);
        }

        $message = $paginated->count() ? 'Shipments fetched successfully' : 'No Shipments available';

        return $this->success(
            $message, 
            [
                'shipments' => ShipmentResource::collection($paginated),
                'pagination' => $pagination
            ]
        );

    }

    /**
     * Store Shipment
     * 
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {   
        $this->authorize('create', [Shipment::class, $request->input('reference_number')]);

        $user = auth()->user();

        $validated = $request->validate([
            'reference_number' => ['required', 'string', function ($attribute, $value, $fail) use ($user) {
                $exists = JobOrder::where('reference_number', $value)
                    ->where('operations_id', $user->id)
                    ->exists();
                if (!$exists) {
                    $fail('Job Order not found or quotation not in ACCEPTED status.');
                }
            }],
        ]);

        $jobOrder = JobOrder::where('reference_number', $validated['reference_number'])->first();

        $quotation = Quotation::where('reference_number', $jobOrder->quotation->reference_number)
            ->where('status', 'ACCEPTED')
            ->first();

        $existingShipment = Shipment::where('quotation_id', $quotation->id)->first();
        if ($existingShipment) {
            return $this->error('Shipment already exists for this job order', 409);
        }

        try {
            DB::beginTransaction();

            $logisticsService = $quotation->logisticsService;
            if (!$logisticsService) {
                return $this->error('Shipment can only be created for logistics quotations', 422);
            }

            if ($logisticsService->service_type === 'IMPORT') {
                $prefix = 'IM';
            } elseif ($logisticsService->service_type === 'EXPORT') {
                $prefix = 'EX';
            } else {
                return $this->error('Invalid logistics service type for shipment creation', 422);
            }
            $lastId = Shipment::max('id') ?? 0;
            $dateSection = Carbon::now()->format('m-Y');
            $idSection = str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);

            $shipment = Shipment::create([
                'reference_number' => "{$prefix}-{$dateSection}-{$idSection}",
                'quotation_id' => $quotation->id,
                'client_id' => $quotation->client_id,
                'as_id' => $quotation->as_id,
                'status' => 'PENDING',
                'company_name' => $quotation->company_name,
                'contact_person' => $quotation->contact_person,
                'contact_number' => $quotation->contact_number,
                'email' => $quotation->email,
                'commodity' => $logisticsService->commodity,
                'cargo_type' => $logisticsService->cargo_type,
                // 'cargo_volume' => $quotation->cargo_volume ?? null,
                'container_size' => $logisticsService->container_size,
                'origin' => $logisticsService->origin,
                'destination' => $logisticsService->destination,
                'remarks' => $logisticsService->remarks,
            ]);

            $quotationFiles = QuotationFile::where('quotation_id', $quotation->id)->get();
            foreach ($quotationFiles as $file) {
                ShipmentFile::create([
                    'shipment_id' => $shipment->id,
                    'quotation_file_id' => $file->id
                ]);
            }

            // Create Group Conversation with all users
            $allUsers = User::pluck('id')->toArray();
            $shipmentConversation = Conversation::create([
                'type' => 'GROUP',
                'name' => $shipment->reference_number,
                'last_message_at' => now(),
            ]);

            // Add all users as participants
            $shipmentConversation->participants()->attach($allUsers);

            // Create system message
            $shipmentConversation->messages()->create([
                'sender_id' => null,
                'type' => 'SHIPMENT_CARD',
                'content' => null,
                'reference_id' => $shipment->id,
                'reference_type' => Shipment::class,
            ]);

            $jobOrder->update([
                'shipment_creation_status' => 'CREATED'
            ]);

            DB::commit();

            return $this->success('Shipment created successfully', new ShipmentResource($shipment), 201);

        } catch (\Exception $e) {
            DB::rollback();
            return $this->error('Failed to create shipment', 500, $e->getMessage());
        }
    }

    /**
     * Show Shipment
     * 
     * Display the specified resource.
     */
    public function show(Shipment $shipment)
    {
        if (!$shipment) {
            return $this->error('Shipment not found', 404);
        }

        return $this->success('Shipment details fetched', new ShipmentResource($shipment), 200);    
    }

    /**
     * Update Shipment
     * 
     * Update the specified resource in storage.
     */
    public function update(Request $request, Shipment $shipment)
    {
        if ($shipment->status === 'DELIVERED') {
            return $this->error('Shipment is already delivered', 400);
        }

        if ($request->has('status')) {
            $shipment->update([
                'status' => $request->status
            ]);
        }
        if ($request->has('remarks')) {
            $shipment->update([
                'remarks' => $request->remarks
            ]);
        }
        

        return $this->success('Shipment status updated', 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
