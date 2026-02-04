<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreQuotationRequest;
use App\Http\Requests\UpdateQuotationRequest;
use App\Http\Resources\QuotationFileResource;
use App\Http\Resources\QuotationResource;
use App\Models\{
    Quotation,
    User,
    ServiceOption,
    QuotationFile,
    Shipment
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
    public function show(Quotation $quotation) {   
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
    public function update(UpdateQuotationRequest $request, Quotation $quotation)
    {
        $user = User::find(auth()->id());

        if (($user->id === $quotation->client_id) || ($user->id === $quotation->as_id)) {
            if ($request->service_options) {
                $stringifiedServiceOptions = implode(',', $request->service['options']);
            } else {
                $stringifiedServiceOptions = null;
            }            

            try {
                DB::beginTransaction();

                $shipped = Shipment::where('quotation_id', $quotation->id)->first();
                if ($shipped) {
                    return $this->error('Shipment already ongoing', 422);
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
        } else {
            return $this->error('Unauthorized', [], 404);
        }

        $file = $request->file('file');
        $directory = 'files';

        //store original file name with extension
        // $originalFileName = $file->getClientOriginalName();

        //store original file name without extension
        $originalFileName = str_replace('.' . $file->extension(), '', $file->getClientOriginalName());

        $fileExists = $quotation->files()->where('type', $type)->exists();

        if ($fileExists) {
            $existingFile = $quotation->files()->where('type', $type)->first();
            $existingFileName = str_replace('/storage/files/', '', $existingFile->file_path);

            $filename = $existingFileName;
        } else {
            $filename = $file->hashName();
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

            //update uploaded quotation status
            if ($type == 'PROPOSAL') {
                $quotation->update([
                    'status' => 'RESPONDED'
                ]);
            }
            
            $message = $quotationFile->wasRecentlyCreated 
                ? 'File uploaded successfully' 
                : 'File updated sucessfully';

            $status = $quotationFile->wasRecentlyCreated ? 201 : 200;

            DB::commit();
            return $this->success($message, new QuotationFileResource($quotationFile), $status);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file',
                'error' => $e->getMessage(),
            ]);
        }
    }


    /**
     * Show Quotation File
     */
    public function showFile(Quotation $quotation) {
        $quotationFile = $quotation->files()->first();
        
        if (! $quotationFile) {
            return $this->success('No quotation file available.', []);
        }

        return $this->success(
            'Quotation file retrieved successfully.', new QuotationFileResource($quotationFile)
        );
    }   
}
