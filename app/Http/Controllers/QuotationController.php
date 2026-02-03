<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\{
    StoreQuotationRequest,
    UpdateQuotationRequest
};
use App\Http\Resources\{
    QuotationResource,
    QuotationFileResource
};
use App\Models\{
    Quotation,
    User,
    ServiceOption,
    QuotationFile
};
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class QuotationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request) {
        $user = auth()->user();
        $query = Quotation::query();

        $quotations = $query->where('client_id', $user->id);
        $requestedQuotations = $quotations->where('status', 'REQUESTED')->get();
        $respondedQuotations = $quotations->where('status', 'RESPONDED')->get();

        if ($request->has('status')) {
            if ($request->status === 'REQUESTED') {
                return $this->success('Requested quotations fetched', QuotationResource::collection($requestedQuotations) ?? null, 200);
            } elseif ($request->status === 'RESPONDED') {
                return $this->success('Responded quotations fetched', QuotationResource::collection($respondedQuotations) ?? null, 200);
            }
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

        try {
            DB::beginTransaction();

            if($user->hasRole('Client')) {
                $stringifiedServiceOptions = implode(',', $request->service['options']);
                $specialists = User::role('Account Specialist')->pluck('id');

                $lastId = Quotation::max('id') ?? 0;
                $dateSection = Carbon::now()->format('m-Y');
                $idSection = str_pad($lastId+1, 3, '0', STR_PAD_LEFT);

                $quotation = Quotation::create([
                    'reference_number' => "QT-{$dateSection}-{$idSection}",
                    'client_id' => $user->id,
                    'as_id' => Faker::create()->randomElement($specialists),
                    'company_name' => $request->company['name'],
                    'company_address' => $request->company['address'],
                    'contact_person' => $request->company['contact_person'],
                    'contact_number' => $request->company['contact_number'],
                    'email' => $request->company['email'],
                    'service_type' => $request->service['type'],
                    'transport_mode' => $request->service['transport_mode'],
                    'service_options' => $stringifiedServiceOptions,
                    'commodity' => $request->commodity['commodity'],
                    'cargo_type' => $request->commodity['cargo_type'],
                    'cargo_volume' => $request->commodity['cargo_volume'] ?? null,
                    'container_size' => $request->commodity['container_size'] ?? null,
                    'origin' => $request->shipment['origin'],
                    'destination' => $request->shipment['destination'],
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

        if (($user->id === $quotation->client_id) || ($user->id === $quotation->as_id)) {
            if ($request->service_options) {
                $stringifiedServiceOptions = implode(',', $request->service['options']);
            } else {
                $stringifiedServiceOptions = null;
            }            

            try {
                DB::beginTransaction();
   
                if ($quotation->status === 'RESPONDED') {
                    return $this->error('Quotation already finalized', 400);
                }

                if ($request->has('status')) {
                    if (!$user->hasRole('Account Specialist')) {
                        return $this->error('Unauthorized', 403);
                    }
                    $quotation->update([
                        'status' => $request->status
                    ]);

                    DB::commit();
                    return $this->success('Quotation status updated', new QuotationResource($quotation), 200);
                }

                $quotation->update([
                    'company_name' => $request->company['name'] ?? $quotation->company_name,
                    'company_address' => $request->company['address'] ?? $quotation->company_address,
                    'contact_person' => $request->company['contact_person'] ?? $quotation->contact_person,
                    'contact_number' => $request->company['contact_number'] ?? $quotation->contact_number,
                    'email' => $request->company['email'] ?? $quotation->email,
                    'service_type' => $request->service['type'] ?? $quotation->service_type,
                    'transport_mode' => $request->service['transport_mode'] ?? $quotation->transport_mode,
                    'service_options' => $stringifiedServiceOptions ?? $quotation->service_options,
                    'commodity' => $request->commodity['commodity'] ?? $quotation->commodity,
                    'cargo_type' => $request->commodity['cargo_type'],
                    'cargo_volume' => $request->commodity['cargo_volume'] ?? $quotation->cargoVolume,
                    'container_size' => $request->commodity['container_size'] ?? $quotation->container_size,
                    'origin' => $request->shipment['origin'] ?? $quotation->origin,
                    'destination' => $request->shipment['destination'] ?? $quotation->destination,
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
        $containerSize = ['1x10', '1x20', '1x40'];

        $quotationOptions = [
            'service_types' => $serviceTypes,
            'transport_modes' => $transportModes,
            'service_options' => $serviceOptions,
            'cargo_type' => $cargoType,
            'container_size' => $containerSize,
        ];

        return $this->success('Quotation options fetched', $quotationOptions, 200);
    }
}
