<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{
    Shipment,
    Quotation,
    QuotationFile,
    ShipmentFile
};
use App\Http\Resources\ShipmentResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ShipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        if ($request->reference_number) {
            $quotation = Quotation::where('reference_number', $request->reference_number)
                ->where('client_id', $user->id)
                ->where('status', 'RESPONDED')
                ->first();

            if (!$quotation) {
                return $this->error('Quotation not found');
            }

            $existingShipment = Shipment::where('quotation_id', $quotation->id)
                ->first();

            if ($existingShipment) {
                return $this->error('Shipment already exists', 401);
            }

            $lastId = Shipment::max('id') ?? 0;
            $dateSection = Carbon::now()->format('m-Y');
            $idSection = str_pad($lastId+1, 3, '0', STR_PAD_LEFT);

            try{
                DB::beginTransaction();

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

                DB::commit();

                return $this->success('Shipment created successfully', new ShipmentResource($shipment), 200);

            } catch (\Exception $e) {
                DB::rollback();
                return $this->error('Something went wrong', 400, $e->getMessage());
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($referenceNumber)
    {
        $shipment = Shipment::where('reference_number', $referenceNumber)
            ->first();

        if (!$shipment) {
            return $this->error('Shipment not found', 404);
        }

        return $this->success('Shipment details fetched', new ShipmentResource($shipment), 200);    
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $referenceNumber)
    {
        $shipment = Shipment::where('reference_number', $referenceNumber)
            ->where('status', 'ONGOING')
            ->first();

        if (!$shipment) {
            return $this->error('Shipment not found', 404);
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
