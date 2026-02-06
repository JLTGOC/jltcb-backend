<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{
    Shipment,
    Quotation,
    QuotationFile,
    ShipmentFile,
    Conversation,
    User
};
use App\Http\Resources\ShipmentResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ShipmentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Shipment::class, 'shipment', [
            'except' => ['store'],
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
                $exists = Quotation::where('reference_number', $value)
                    ->where('client_id', $user->id)
                    ->where('status', 'RESPONDED')
                    ->exists();
                if (!$exists) {
                    $fail('The quotation not found or not in RESPONDED status.');
                }
            }],
        ]);

        $quotation = Quotation::where('reference_number', $validated['reference_number'])
            ->where('client_id', $user->id)
            ->where('status', 'RESPONDED')
            ->first();

        $existingShipment = Shipment::where('quotation_id', $quotation->id)->first();
        if ($existingShipment) {
            return $this->error('Shipment already exists for this quotation', 409);
        }

        try {
            DB::beginTransaction();

            $lastId = Shipment::max('id') ?? 0;
            $dateSection = Carbon::now()->format('m-Y');
            $idSection = str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);

            $shipment = Shipment::create([
                'reference_number' => "IM-{$dateSection}-{$idSection}",
                'quotation_id' => $quotation->id,
                'client_id' => $user->id,
                'as_id' => $quotation->as_id,
                'status' => 'ONGOING',
                'company_name' => $quotation->company_name,
                'contact_person' => $quotation->contact_person,
                'contact_number' => $quotation->contact_number,
                'email' => $quotation->email,
                'commodity' => $quotation->commodity,
                'cargo_type' => $quotation->cargo_type,
                'cargo_volume' => $quotation->cargo_volume ?? null,
                'container_size' => $quotation->container_size ?? null,
                'origin' => $quotation->origin,
                'destination' => $quotation->destination,
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
        if ($shipment->status !== 'ONGOING') {
            return $this->error('Shipment not found or not in ONGOING status', 404);
        }

        $shipment->update([
            'status' => 'DELIVERED'
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
