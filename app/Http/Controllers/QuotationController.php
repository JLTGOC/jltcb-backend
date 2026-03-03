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
    Shipment,
    Message,
};
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Spatie\Searchable\Search;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class QuotationController extends Controller
{

    public function __construct() {
        $this->authorizeResource(Quotation::class, 'quotation');
        $this->middleware('can:enumQuotationOptions,' . Quotation::class)->only('enumQuotationOptions');
        $this->middleware('can:upload,quotation')->only('upload');
        $this->middleware('can:showFile,quotation')->only('showFile');
    }

    /**
     * Index Quotations
     * 
     * Display a listing of the resource.
     */
    public function index(Request $request) {
        $user = auth()->user();
        $query = Quotation::query();
        if ($user->hasRole('Client')) {
            $query->where('client_id', $user->id);
        } elseif ($user->hasRole('Account Specialist')) {
            $query->where('as_id', $user->id);
        }

        $request->validate([
            'filter.status' => 'sometimes|in:REQUESTED,RESPONDED',
            'search' => 'sometimes|string'
        ]);

        $quotations = QueryBuilder::for($query)
            ->allowedFilters([AllowedFilter::exact('status')]);

        $status = $request->input('filter.status');
        if ($status) {
            $quotations->where('status', $status);
        }

        if ($request->search) {
            $search = $request->search;
            $searchIds = (new Search())
                ->registerModel(Quotation::class, ['reference_number', 'id', 'contact_person', 'company_name', 'email', 'commodity', 'origin', 'destination', 'cargo_type'])
                ->search($search)
                ->collect()
                ->pluck('searchable')
                ->map->id
                ->filter()
                ->values();

            $clientSearchIds = Quotation::query()
                ->leftJoin('users', 'quotations.client_id', '=', 'users.id')
                ->where('users.full_name', 'like', "%{$search}%")
                ->select('quotations.id')
                ->pluck('id');

            $mergedIds = $searchIds->merge($clientSearchIds)->unique()->values();

            if ($mergedIds->isEmpty()) {
                return $this->success('No quotations found', [], 200);
            }

            $quotations->whereIn('id', $mergedIds);
        }

        $results = $quotations->orderBy('created_at', 'desc')->get();

        if ($user->hasRole('Account Specialist') && $request->filter['status'] === 'REQUESTED') {
            $results = $quotations->with('client')->get();
            
            $results = $results->groupBy('client_id')->map(function ($userQuotations) {
                $firstQuotation = $userQuotations->first();
                $client = User::where('id', $firstQuotation->client_id)->value('full_name');

                return [
                    'name' => $client,
                    'request_count' => $userQuotations->count(),
                    'quotations' => $userQuotations->map(function ($quotation) {
                        $card = Message::where('reference_id', $quotation->id)
                            ->where('type', 'QUOTATION_CARD')
                            ->first();
                        if ($card) {
                            $conversationId = $card->conversation_id;
                        }

                        return [
                            'id' => $quotation->id,
                            'date' => $quotation->created_at->format('Y/m/d'),
                            'person_in_charge' => $quotation->accountSpecialist->full_name,
                            'commodity' => $quotation->commodity,
                            'conversation_id' => $conversationId ?? null
                        ];
                    })->values(),
                ];
            })->values();
        } else {
            $results = $results->map(function ($result) use ($user,$request) {
                if ($request->has('filter.status')) {
                    if ($user->hasRole('Client') && $request->filter['status'] === 'RESPONDED') {
                        $status = 'NEW';
                        if (Shipment::where('quotation_id', $result->id)->exists()) {
                            $status = 'ACCEPTED';
                        }
                    }
                    $card = Message::where('reference_id', $result->id)
                        ->where('type', 'QUOTATION_CARD')
                        ->first();
                    if ($card) {
                        $conversationId = $card->conversation_id;
                    }
                }
                return [
                    'id' => $result->id,
                    'client_name' => $result->client->full_name,
                    'reference_number' => $result->reference_number,
                    'commodity' => $result->commodity,
                    'date' => $result->created_at->format('Y/m/d'),
                    'status' => $status ?? $result->status,
                    'conversation_id' => $conversationId ?? null
                ];
            });
        }

        if ($results->isEmpty()) {
            return $this->success('No quotations found', [], 200);
        }

        return $this->success('All quotations fetched', $results, 200);
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
                // 'cargo_volume' => $request->commodity['cargo_volume'] ?? null,
                'container_size' => $request->commodity['container_size'] ?? null,
                'origin' => $request->shipment['origin'],
                'destination' => $request->shipment['destination'],
                'remarks' => $request->remarks,
            ]);

            // if ($quotation->cargo_type === 'CONTAINERIZED' && isset($quotation->cargo_volume)) {
            //     $quotation->update([
            //         'cargo_volume' => null
            //     ]);
            // } else
            if ($quotation->cargo_type === 'LCL' && isset($quotation->container_size)) {
                $quotation->update([
                    'container_size' => null
                ]);
            }

            // Upload client documents
            $newFiles = $request->file('documents');

            $fileUploaded = $this->uploadClientDocuments(
                $quotation,
                $request->user(), 
                $newFiles =  $newFiles
            );

            if ($fileUploaded !== true) {
                return $this->error($fileUploaded->getMessage());
            }

            DB::commit();

            return $this->success('Quotation request submitted', new QuotationResource($quotation), 200);
            
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

        if (((int) $user->id === (int) $quotation->client_id) || ((int) $user->id === (int) $quotation->as_id)) {
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
                    // 'cargo_volume' => $request->commodity['cargo_volume'] ?? $quotation->cargoVolume,
                    'container_size' => $request->commodity['container_size'] ?? $quotation->container_size,
                    'origin' => $request->shipment['origin'] ?? $quotation->origin,
                    'destination' => $request->shipment['destination'] ?? $quotation->destination,
                    'remarks' => $request->remarks,
                ]);

                // if ($quotation->cargo_type === 'CONTAINERIZED' && isset($quotation->cargo_volume)) {
                //     $quotation->update([
                //         'cargo_volume' => null
                //     ]);
                // } else
                if ($quotation->cargo_type === 'LCL' && isset($quotation->container_size)) {
                    $quotation->update([
                        'container_size' => null
                    ]);
                }

                 // Re-upload client documents
                $removedFileIds = $request->input('removed_documents', []);
                $newFiles = $request->file('documents', []);

                $fileUploaded = $this->uploadClientDocuments(
                    $quotation,
                    $request->user(), 
                    $newFiles = $newFiles, 
                    $removedFileIds = $removedFileIds
                );

                if ($fileUploaded !== true) {
                    return $this->error($fileUploaded->getMessage());
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
     * Destroy Quotation
     * 
     * Remove the specified resource from storage.
     */
    public function destroy(Quotation $quotation)
    {
        $quotation->delete();

        return $this->success('Quotation deleted', [], 200);
    }

    /**
     * Enum Quotation Options
     * 
     * Fetch enumeration options for quotations
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
     * Upload Quotation File
     * 
     * Uploads a file for the quotation
     */
    public function upload(Quotation $quotation, Request $request) {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf']
        ]);

        $user = $request->user();

        $file = $request->file('file');
        $directory = 'files';
        $originalFileName = str_replace('.' . $file->extension(), '', $file->getClientOriginalName());
        $type = 'PROPOSAL';

        $existingFile = $quotation->files()->where('type', $type)->first();

        if ($existingFile) {
            $existingFileName = str_replace('/files/', '', $existingFile->file_path);
            $filename = $existingFileName;
        } else {
            $filename = $file->hashName();
        }
       
        DB::beginTransaction();
        try {

            $path = $file->storeAs($directory, $filename, 'public');

            $quotationFile = QuotationFile::updateOrCreate(
                [
                    'quotation_id' => $quotation->id,
                    'uploaded_by' => $user->id
                ],
                [
                    'file_path' => $path,
                    'type' => $type,
                    'original_file_name' => $originalFileName
                ],
            );

            $quotation->update([
                'status' => 'RESPONDED'
            ]);
            
            $message = $quotationFile->wasRecentlyCreated 
                ? 'Quotation file uploaded successfully' 
                : 'Quotation file updated sucessfully';

            $status = $quotationFile->wasRecentlyCreated ? 201 : 200;

            DB::commit();
            return $this->success($message, new QuotationFileResource($quotationFile), $status);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(
                'Failed to upload quotation file', 500, $e->getMessage()
            );
        }
    } 

    /**
     * Upload Client Documents
     * 
     * Upload files for client documents
     */
    private function uploadClientDocuments(
        Quotation $quotation,
        User $user,
        array $newFiles = [],
        array $removedFileIds = []
    ) {
        $type = 'REQUESTED';

        DB::beginTransaction();
        try {
            // Delete only the files explicitly marked for removal
            if (!empty($removedFileIds)) {
                $filesToRemove = $quotation->files()->whereIn('id', $removedFileIds)->get();
                foreach ($filesToRemove as $file) {
                    Storage::disk('public')->delete($file->file_path);
                    $file->delete();
                }
            }

            // Upload new files if provided
            if (!empty($newFiles)) {
                foreach ($newFiles as $file) {
                    $filename = $file->hashName();
                    $path = $file->storeAs('files', $filename, 'public');
                    $originalFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                    QuotationFile::create([
                        'quotation_id' => $quotation->id,
                        'file_path' => $path,
                        'original_file_name' => $originalFileName,
                        'uploaded_by' => $user->id,
                        'type' => $type,
                    ]);
                }
            }

            DB::commit();
            return true; // success
        } catch (\Exception $e) {
            DB::rollBack();
            return $e; // return exception for error handling
        }
    }
}
