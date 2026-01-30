<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreQuotationRequest;
use App\Http\Resources\QuotationResource;
use App\Models\{
    Quotation,
    User
};
use Illuminate\Support\Facades\DB;

class QuotationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {
        //
    }

    /**
     * Store
     * 
     * Request new quotation
     */
    public function store(Request $request)
    {
        $user = User::find(auth()->id());

        try {
            DB::beginTransaction();

            if($user->role('CLIENT')) {
                $stringifiedServiceOptions = implode(',', $request->serviceOptions);

                $quotation = Quotation::create([
                    'reference_number' => $this->quotationReferenceNumber(),
                    'user_id' => $user->id,
                    'company_name' => $request->companyName,
                    'company_address' => $request->companyAddress,
                    'contact_person' => $request->contactPerson,
                    'contact_number' => $request->contactNumber,
                    'email' => $request->email,
                    'service_type' => $request->serviceType,
                    'transport_mode' => $request->transportMode,
                    'service_options' => $stringifiedServiceOptions,
                    'commodity' => $request->commodity,
                    'cargo_volume' => $request->cargoVolume,
                    'container_size' => $request->containerSize ?? null,
                    'origin' => $request->origin,
                    'destination' => $request->destination,
                ]);

                DB::commit();

                return $this->success('Quotation request submitted', $quotation, 200);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return $this->error('Something went wrong', 400, $e);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($referenceNumber) {
        $quotation = Quotation::where('reference_number', $referenceNumber)->first();
        $quotationCollection = new QuotationResource($quotation);

        return $this->success('Quotation details fetched successfully', $quotationCollection, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
