<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreQuotationRequest;
use App\Http\Resources\QuotationFileResource;
use App\Http\Resources\QuotationResource;
use App\Models\{
    Quotation,
    User,
    ServiceOption,
    QuotationFile
};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
                $stringifiedServiceOptions = implode(',', $request->service_options);
                $specialists = User::role('Account Specialist')->pluck('id');

                if ($request->cargo_type === 'CONTAINERIZED') {
                    $request->validate([
                        'container_size' => 'required|string'
                    ]);
                } elseif ($request->cargo_type === 'LCL') {
                    $request->validate([
                        'cargo_volume' => 'required|numeric|min:1'
                    ]);
                }

                $lastId = Quotation::max('id') ?? 0;
                $dateSection = Carbon::now()->format('m-Y');
                $idSection = str_pad($lastId+1, 3, '0', STR_PAD_LEFT);

                $quotation = Quotation::create([
                    'reference_number' => "QT-{$dateSection}-{$idSection}",
                    'client_id' => $user->id,
                    'as_id' => Faker::create()->randomElement($specialists),
                    'company_name' => $request->company_name,
                    'company_address' => $request->company_address,
                    'contact_person' => $request->contact_person,
                    'contact_number' => $request->contact_number,
                    'email' => $request->email,
                    'service_type' => $request->service_type,
                    'transport_mode' => $request->transport_mode,
                    'service_options' => $stringifiedServiceOptions,
                    'commodity' => $request->commodity,
                    'cargo_type' => $request->cargo_type,
                    'cargo_volume' => $request->cargo_volume ?? null,
                    'container_size' => $request->container_size ?? null,
                    'origin' => $request->origin,
                    'destination' => $request->destination,
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

        if ($user->hasRole('Account Specialist')) {
            if ($request->service_options) {
                $stringifiedServiceOptions = implode(',', $request->service_options);
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

                if ($request->cargo_type === 'CONTAINERIZED') {
                    $request->validate([
                        'container_size' => 'required|string'
                    ]);
                } elseif ($request->cargo_type === 'LCL') {
                    $request->validate([
                        'cargo_volume' => 'required|numeric|min:1'
                    ]);
                }

                $quotation->update([
                    'company_name' => $request->company_name ?? $quotation->company_name,
                    'company_address' => $request->company_address ?? $quotation->company_address,
                    'contact_person' => $request->contact_person ?? $quotation->contact_person,
                    'contact_number' => $request->contact_number ?? $quotation->contact_number,
                    'email' => $request->email ?? $quotation->email,
                    'service_type' => $request->service_type ?? $quotation->service_type,
                    'transport_mode' => $request->transport_mode ?? $quotation->transport_mode,
                    'service_options' => $stringifiedServiceOptions ?? $quotation->service_options,
                    'commodity' => $request->commodity ?? $quotation->commodity,
                    'cargo_type' => $request->cargo_type,
                    'cargo_volume' => $request->cargo_volume ?? $quotation->cargoVolume,
                    'container_size' => $request->container_size ?? $quotation->container_size,
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
    
    /**
     * Upload/Update Quotation File
     */
    public function upload(Quotation $quotation, Request $request) {
        $request->validate([
            'quotation' => ['required', 'file', 'mimes:pdf']
        ]);

        $file = $request->file('quotation');
        $directory = 'quotations/files';
        $filename = $file->getClientOriginalName();
       
        DB::beginTransaction();
        try {
            $path = $file->storeAs($directory, $filename, 'public');
            $url = Storage::url($path);

            $quotationFile = QuotationFile::updateOrCreate(
                ['quotation_id' => $quotation->id],
                ['file_path' => $url],
            );

            $message = $quotationFile->wasRecentlyCreated 
                ? 'Quotation file uploaded successfully' 
                : 'Quotation file updated sucessfully';

            $status = $quotationFile->wasRecentlyCreated ? 201 : 200;

            DB::commit();
            return $this->success($message, new QuotationFileResource($quotationFile), $status);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload quotation file',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
