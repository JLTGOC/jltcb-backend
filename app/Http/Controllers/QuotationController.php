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
            $stringifiedServiceOptions = implode(',', $request->serviceOptions) ?? null;

            try {
                DB::beginTransaction();

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
                    'cargo_volume' => $request->cargoVolume ?? $quotation->cargo_volume,
                    'container_size' => $containerSize ?? $quotation->container_size,
                    'origin' => $request->origin ?? $quotation->origin,
                    'destination' => $request->destination ?? $quotation->destination,
                ]);

                DB::commit();

                return $this->success('Quotation request updated', $quotation, 200);

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
     * Index Service Options
     */
    public function indexServiceOptions() {
        $serviceOptionNames = ServiceOption::pluck('name');

        return $this->success('Service options fetched', $serviceOptionNames, 200);
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
