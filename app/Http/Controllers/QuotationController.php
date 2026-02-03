<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreQuotationRequest;
use App\Http\Resources\QuotationFileResource;
use App\Http\Resources\QuotationResource;
use App\Models\{
    Quotation,
    User,
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
     * Store
     * 
     * Request new quotation
     */
    public function store(StoreQuotationRequest $request)
    {
        $user = User::find(auth()->id());

        try {
            DB::beginTransaction();

            if($user->role('Client')) {
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

    /**
     * Upload/Update Quotation File
     */
    public function upload(Quotation $quotation, Request $request) {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf']
        ]);

        $user = $request->user();

        if ($user->hasRole('Client')) {
            $type = 'REQUESTED';
        } elseif($user->hasRole('Account Specialist')) {
            $type = 'PROPOSAL';
        }

        $file = $request->file('file');
        $directory = 'files';
        $extension = $file->getClientOriginalExtension();
        $originalFileName = $file->getClientOriginalName();

        $quoteFile = $quotation->files()->where('type', $type)->exists();

        if ($quoteFile) {
            $existingFile = $quotation->files()->where('type', $type)->first();
            $existingFileName = str_replace('/storage/files/', '', $existingFile->file_path);

            $filename = $existingFileName;
        } else {
            $filename = uniqid() . '.' . $extension;
        }
       
        DB::beginTransaction();
        try {

            $path = $file->storeAs($directory, $filename, 'public');
            $url = Storage::url($path);

            $quotationFile = QuotationFile::updateOrCreate(
                [
                    'quotation_id' => $quotation->id,
                    'uploaded_by' => $user->id
                ],
                [
                    'file_path' => $url,
                    'type' => $type,
                    'original_file_name' => $originalFileName
                ],
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


    /**
     * Show Quotation File
     */
    public function showFile(Quotation $quotation) {

        $quotationFile = $quotation->file;
        
        if (! $quotationFile) {
            return $this->success('No quotation file available.', []);
        }

        return $this->success(
            'Quotation file retrieved successfully.', new QuotationFileResource($quotationFile)
        );

    }   

}
