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
    ActivityLog,
};
use App\Http\Resources\ShipmentResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

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
        $opsNames = User::role(['Operations', 'Client Success'])->pluck('full_name');
        $shipmentCounts = [
            'ALL' => $allShipmentsCount = Shipment::count(),
            'NOT YET DEPARTED' => $notYetDepartedCount = Shipment::where('status', 'NOT YET DEPARTED')->count(),
            'IN TRANSIT' => $inTransitCount = Shipment::where('status', 'IN TRANSIT')->count(),
            'ARRIVED' => $arrivedCount = Shipment::where('status', 'ARRIVED')->count(),
            'BERTHED' => $berthedCount = Shipment::where('status', 'BERTHED')->count(),
            'DISCHARGED' => $dischargedCount = Shipment::where('status', 'DISCHARGED')->count(),
            'DELIVERED' => $deliveredCount = Shipment::where('status', 'DELIVERED')->count(),
        ];

        $perPage = $request->input('perPage', 10);
        $search = $request->input('search');
        $platform = $request->header('Platform', 'mobile');
        $request->validate([
            'filter.status' => ['sometimes', function($attribute, $value, $fail) use ($request) {
                $platform = strtolower($request->header('Platform', 'mobile'));
                $isWeb = $platform === 'web';
                if ($isWeb) {
                    $allowedStatuses = ['ALL', 'NOT YET DEPARTED', 'IN TRANSIT', 'ARRIVED', 'BERTHED', 'DISCHARGED', 'DELIVERED'];
                } else {
                    $allowedStatuses = ['ONGOING', 'DELIVERED'];
                }
                
                if (!in_array($value, $allowedStatuses)) {
                    $fail("The {$attribute} filter must be one of: " . implode(', ', $allowedStatuses));
                }
            }],
            'search' => 'sometimes|string|max:255',
            'filter.eta' => 'sometimes|date',
            'filter.person_in_charge' => 'sometimes|in:' . implode(',', User::role(['Operations', 'Client Success'])->pluck('full_name')->toArray()),
        ]);

        $user = $request->user();
        $status = $request->input('filter.status');

        // Allows conditional base query depending on role
        $baseQuery = $user->hasRole('Client') ? Shipment::where('client_id', $user->id) : Shipment::class;
        $shipmentsQuery = QueryBuilder::for($baseQuery)
            ->defaultSort('-created_at', '-id');

        if (isset($request->filter['status'])) {
            if ($status !== 'ALL') {
                if ($status === 'ONGOING') {
                    $shipmentsQuery->whereIn('status', ['NOT YET DEPARTED', 'IN TRANSIT', 'ARRIVED', 'BERTHED', 'DISCHARGED']);
                } else {
                    $shipmentsQuery->where('status', $status);
                }
            }
        } 

        if ($search) {
            $shipmentsQuery->where(function ($query) use ($search) {
                $query->where('reference_number', 'LIKE', '%' . $search . '%')
                    ->orWhere('status', 'LIKE', '%' . $search . '%')
                    ->orWhere('commodity', 'LIKE', '%' . $search . '%');
            });

            $clientIds = User::where('full_name', 'LIKE', '%' . $search . '%')->pluck('id');

            $shipmentsQuery->orWhereIn('client_id', $clientIds);
        }

        if ($request->has('filter.eta')) {
            $shipmentsQuery->whereHas('jobOrder.jobOrderShipment', function ($query) use ($request) {
                $query->whereDate('eta', $request->input('filter.eta'));
            });
        }

        if ($request->has('filter.person_in_charge')) {
            $shipmentsQuery->where('operations_id', User::where('full_name', $request->input('filter.person_in_charge'))->value('id'));
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
                'ops_names' => $opsNames,
                'shipments' => ShipmentResource::collection($paginated),
                'pagination' => $pagination,
                'shipment_counts' => $shipmentCounts
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
                'job_order_id' => $jobOrder->id,
                'client_id' => $quotation->client_id,
                'as_id' => $quotation->as_id,
                'operations_id' => $jobOrder->operations_id,
                'status' => 'NOT YET DEPARTED',
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

            $activityLog = ActivityLog::create([
                'user_id' => auth()->id(),
                'subject_id' => $shipment->id,
                'subject_type' => Shipment::class,
                'action' => 'Shipment Created',
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

        $activityLog = ActivityLog::create([
            'user_id' => auth()->id(),
            'subject_id' => $shipment->id,
            'subject_type' => Shipment::class,
            'action' => 'Shipment ' . ucwords(mb_strtolower($shipment->status)),
        ]);

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
