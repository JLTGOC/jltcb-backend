<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\{
    StoreQuotationRequest,
    UpdateQuotationRequest
};
use App\Http\Resources\QuotationResource;
use App\Models\{
    Quotation,
    User,
    ServiceOption
};
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class QuotationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {
        //
    }

    /**
     * Store Quotation
     * 
     * Request new quotation
     */
    public function store(StoreQuotationRequest $request)
    {
        $user = User::find(auth()->id());

        try {
            DB::beginTransaction();

            if($user->hasRole('Client')) {
                $stringifiedServiceOptions = implode(',', $request->serviceOptions);
                $specialists = User::role('Account Specialist')->pluck('id');

                if ($request->cargoType === 'CONTAINERIZED') {
                    $request->validate([
                        'containerSize' => 'required|string'
                    ]);
                } elseif ($request->cargoType === 'LCL') {
                    $request->validate([
                        'cargoVolume' => 'required|numeric|min:1'
                    ]);
                }

                $quotation = Quotation::create([
                    'client_id' => $user->id,
                    'as_id' => Faker::create()->randomElement($specialists),
                    'company_name' => $request->companyName,
                    'company_address' => $request->companyAddress,
                    'contact_person' => $request->contactPerson,
                    'contact_number' => $request->contactNumber,
                    'email' => $request->email,
                    'service_type' => $request->serviceType,
                    'transport_mode' => $request->transportMode,
                    'service_options' => $stringifiedServiceOptions,
                    'commodity' => $request->commodity,
                    'cargo_type' => $request->cargoType,
                    'cargo_volume' => $request->cargoVolume ?? null,
                    'container_size' => $request->containerSize ?? null,
                    'origin' => $request->origin,
                    'destination' => $request->destination,
                ]);

                $dateSection = Carbon::now()->format('m-Y');
                $idSection = str_pad($quotation->id, 3, '0', STR_PAD_LEFT);

                $quotation->update([
                    'reference_number' => "QT-{$dateSection}-{$idSection}"
                ]);

                if ($quotation->cargo_type === 'CONTAINERIZED' && isset($quotation->cargo_volume)) {
                    $quotation->update([
                        'cargo_volume' => null
                    ]);
                } elseif ($quotation->cargo_type === 'LCL' && isset($quotation->container_size)) {
                    $quotation->update([
                        'container_size' => null
                    ]);
                }

                DB::commit();

                return $this->success('Quotation request submitted', new QuotationResource($quotation), 200);
            } else {
                return $this->error('Unauthorized', 403);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return $this->error('Something went wrong', 400, $e);
        }
    }

    /**
     * Show Quotation
     * 
     * Show individual quotation details
     */
    public function show($referenceNumber) {
        $quotation = Quotation::where('reference_number', $referenceNumber)->first();
        
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
    public function update(UpdateQuotationRequest $request, $referenceNumber)
    {
        $user = User::find(auth()->id());
        $quotation = Quotation::where('reference_number', $referenceNumber)->first();

        if ($user->hasRole('Account Specialist')) {
            $containerSize = $request->containerSize ?? null;

            if ($request->serviceOptions) {
                $stringifiedServiceOptions = implode(',', $request->serviceOptions);
            } else {
                $stringifiedServiceOptions = null;
            }            

            try {
                DB::beginTransaction();

                if ($quotation->status === 'RESPONDED') {
                    return $this->error('Quotation already finalized', 400);
                }

                if ($request->has('status')) {
                    $quotation->update([
                        'status' => $request->status
                    ]);

                    DB::commit();
                    return $this->success('Quotation status updated', new QuotationResource($quotation), 200);
                }

                if ($request->cargoType === 'CONTAINERIZED') {
                    $request->validate([
                        'containerSize' => 'required|string'
                    ]);
                } elseif ($request->cargoType === 'LCL') {
                    $request->validate([
                        'cargoVolume' => 'required|numeric|min:1'
                    ]);
                }

                $quotation->update([
                    'company_name' => $request->companyName ?? $quotation->company_name,
                    'company_address' => $request->companyAddress ?? $quotation->company_address,
                    'contact_person' => $request->contactPerson ?? $quotation->contact_person,
                    'contact_number' => $request->contactNumber ?? $quotation->contact_number,
                    'email' => $request->email ?? $quotation->email,
                    'service_type' => $request->serviceType ?? $quotation->service_type,
                    'transport_mode' => $request->transportMode ?? $quotation->transport_mode,
                    'service_options' => $stringifiedServiceOptions ?? $quotation->service_options,
                    'commodity' => $request->commodity ?? $quotation->commodity,
                    'cargo_type' => $request->cargoType,
                    'cargo_volume' => $request->cargoVolume ?? $quotation->cargoVolume,
                    'container_size' => $containerSize ?? $quotation->container_size,
                    'origin' => $request->origin ?? $quotation->origin,
                    'destination' => $request->destination ?? $quotation->destination,
                ]);

                if ($quotation->cargo_type === 'CONTAINERIZED' && isset($quotation->cargo_volume)) {
                    $quotation->update([
                        'cargo_volume' => null
                    ]);
                } elseif ($quotation->cargo_type === 'LCL' && isset($quotation->container_size)) {
                    $quotation->update([
                        'container_size' => null
                    ]);
                }

                DB::commit();

                return $this->success('Quotation request updated', new QuotationResource($quotation), 200);

            } catch (\Exception $e) {
                DB::rollback();
                return $this->error('Something went wrong', 400, $e);
            }
        } else {
            return $this->error('Unauthorized', 403);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Enum Quotation Options
     */
    public function enumQuotationOptions() {
        $serviceTypes = ['IMPORT', 'EXPORT', 'BUSINESS SOLUTION'];
        $transportModes = ['AIR', 'SEA'];
        $serviceOptions = ServiceOption::pluck('name');
        $cargoType = ['CONTAINERIZED', 'LCL'];

        $quotationOptions = [
            'serviceTypes' => $serviceTypes,
            'transportModes' => $transportModes,
            'serviceOptions' => $serviceOptions,
            'cargoType' => $cargoType,
        ];

        return $this->success('Quotation options fetched', $quotationOptions, 200);
    }
}
