<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIssuedQuotationRequest;
use App\Http\Requests\UpdateIssuedQuotationRequest;
use App\Http\Resources\IssuedQuotationResource;
use App\Models\IssuedQuotation\AuthorizedSignatories;
use App\Models\IssuedQuotation\IssuedQuotation;
use App\Models\Quotation;
use App\Services\QuotationFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IssuedQuotationController extends Controller
{
    protected $quotationFileService;

    public function __construct(QuotationFileService $quotationFileService)
    {
        $this->quotationFileService = $quotationFileService;
    }

    /**
     * Index Issued Quotations
     * 
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store Issued Quotations
     * 
     * Store a newly created resource in storage.
     */
    public function store(StoreIssuedQuotationRequest $request, Quotation $quotation)
{
        $this->authorize('create', [IssuedQuotation::class, $quotation]);

        $as = $request->user();

        $signatureFilePath = null;
        $quotationFilePath = null;

        $validated = $request->validated();
        
        DB::beginTransaction();

        try {
            $issuedQuotation = $quotation->issuedQuotations()->create([
                'template_id' => $validated['template_id'],
                'issued_by' => $as->id,
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'rate_validity' => $validated['rate_validity'],
                'currency' => $validated['currency'],
            ]);

            $issuedQuotation->detailValues()->createMany($validated['detail_values']);

            foreach ($validated['charges'] as $requestCharge) {
                $charge = $issuedQuotation->charges()->create([
                    'name' => $requestCharge['name'],
                    'subtotal' => collect($requestCharge['items'])->sum('amount'),
                ]);

                $items = collect($requestCharge['items'])->map(function($item) use ($validated) {
                    $data = [
                        'receipt_charge_label' => $item['receipt_charge_label'],
                        'amount' => $item['amount'],
                        'uom' => $item['uom'],
                    ];

                    if ($item['uom'] === 'PER CONTAINER') {
                        return [
                            ...$data,
                            'quantity' => $item['quantity'],
                            'container_size' => $item['container_size']
                        ];
                    }

                    return $data;
                })->toArray();

                $charge->items()->createMany($items);
            }

            $issuedQuotation->standardConfig()->create($validated['standard_config']);

            $signatureFilePath = $request->file('signatory.signature_file')
                ->store('signatures', 'local');

            $issuedQuotation->authorizedSignatory()->create([
                ...$validated['signatory'],
                'signature_file_path' => $signatureFilePath,
            ]);

            $quotationFile = $request->file('issued_quotation_file');
            $quotationFilePath = $quotationFile->store('files', 'local');
            $this->quotationFileService->uploadQuotationFile($quotation, $quotationFile, $as);

            $quotation->update([
                'status' => 'RESPONDED',
                'created_by' => $as->id
            ]);

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();

            if ($signatureFilePath) {
                Storage::disk('local')->delete($signatureFilePath);
            }

            if ($quotationFilePath) {
                Storage::disk('local')->delete($quotationFilePath);
            }

            return $this->error(
                'An error occurred while storing issued quotation. Please try again.', 
                data: [
                    "error_message" => $e->getMessage()
                ]
            );
        }

        $issuedQuotation->load([
            'detailValues',
            'charges.items',
            'authorizedSignatory',
            'standardConfig',
            'template.quotationFields',
            'quotation.files'
        ]);

        return $this->success(
            'Issued Quotation stored successfully',
            new IssuedQuotationResource($issuedQuotation),
            201
        );
    }

    /**
     * Show Issued Quotation
     * 
     * Display the specified resource.
     */
    public function show(Quotation $quotation, IssuedQuotation $issuedQuotation)
    {
        $this->authorize('view', [$quotation, $issuedQuotation]);

        $issuedQuotation->load([
            'detailValues', 'charges.items', 'authorizedSignatory', 'standardConfig', 'template.quotationFields', 'quotation.files'
        ]);

        return $this->success(
            'Issued Quotation fetched successfully', 
            new IssuedQuotationResource($issuedQuotation),
        );
    }

    /**
     * Update Issued Quotation
     * 
     * Update the specified resource in storage.
     */
    public function update(UpdateIssuedQuotationRequest $request, Quotation $quotation, IssuedQuotation $issuedQuotation)
{
        $this->authorize('update', [$quotation, $issuedQuotation]);

        $newSignaturePath = null;
        $newQuotationFilePath = null;
        $oldSignaturePath = $issuedQuotation->authorizedSignatory->signature_file_path;

        $validated = $request->validated();

        DB::beginTransaction();

        try {
            $issuedQuotation->update([
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'rate_validity' => $validated['rate_validity'],
                'currency' => $validated['currency']
            ]);

            $issuedQuotation->detailValues()->delete();
            $issuedQuotation->detailValues()->createMany($validated['detail_values']);

            $issuedQuotation->charges()->delete();
            foreach ($validated['charges'] as $requestCharge) {
                $charge = $issuedQuotation->charges()->create([
                    'name' => $requestCharge['name'],
                    'subtotal' => collect($requestCharge['items'])->sum('amount'),
                ]);

                $items = collect($requestCharge['items'])->map(function($item) use ($validated) {
                    $data = [
                        'receipt_charge_label' => $item['receipt_charge_label'],
                        'amount' => $item['amount'],
                        'uom' => $item['uom'],
                    ];

                    if ($item['uom'] === 'PER CONTAINER') {
                        $data['quantity'] = $item['quantity'];
                        $data['container_size'] = $item['container_size'];
                    }

                    return $data;
                })->toArray();

                $charge->items()->createMany($items);
            }

            $issuedQuotation->standardConfig()->delete();
            $issuedQuotation->standardConfig()->create($validated['standard_config']);

            if ($request->hasFile('signatory.signature_file')) {
                $newSignaturePath = $request->file('signatory.signature_file')->store('signatures', 'local');
            }

            $validatedSignatory = $validated['signatory'];
            $issuedQuotation->authorizedSignatory()->update([
                'closing_statement' => $validatedSignatory['closing_statement'],
                'is_authorized_signatory' => $validatedSignatory['is_authorized_signatory'],
                'authorized_signatory_name' => $validatedSignatory['authorized_signatory_name'],
                'position' => $validatedSignatory['position'],
                'signature_file_path' => $newSignaturePath ?? $oldSignaturePath
            ]);

            $oldquotationFile = $quotation->files()->where('type', 'PROPOSAL')->first();
            Storage::disk('local')->delete($oldquotationFile->file_path);
            $oldquotationFile->delete();

            $quotationFile = $request->file('issued_quotation_file');
            $newQuotationFilePath = $quotationFile->store('files', 'local');

            $this->quotationFileService->uploadQuotationFile($quotation, $quotationFile, $request->user());

            DB::commit();

            if ($newSignaturePath && $oldSignaturePath) {
                Storage::disk('local')->delete($oldSignaturePath);
            }

        } catch (\Throwable $e) {
            DB::rollBack();

            if ($newSignaturePath) {
                Storage::disk('local')->delete($newSignaturePath);
            }

            if ($newQuotationFilePath) {
                Storage::disk('local')->delete($newQuotationFilePath);
            }

            return $this->error(
                'An error occurred while updating issued quotation. Please try again.', 
                data: [
                    "error_message" => $e->getMessage()
                ]
            );
        }

        $issuedQuotation->load([
            'detailValues', 'charges.items', 'authorizedSignatory', 'standardConfig', 'template.quotationFields', 'quotation.files'
        ]);

        return $this->success(
            'Issued Quotation updated successfully',
            new IssuedQuotationResource($issuedQuotation),
            201
        );
    }
    /**
     * Delete Issued Quotation
     * 
     * Remove the specified resource from storage.
     */
    public function destroy(Quotation $quotation, IssuedQuotation $issuedQuotation)
    {
        $this->authorize('delete', [$quotation, $issuedQuotation]);

        $issuedQuotation->delete();

        return $this->success('Issued Quotation deleted successfully');
    }

    /**
     * Serve Signature file
     * 
     * Used for securing temporary Urls for authorized signature files
     */
    public function viewSignature($id)
    {
        $signatory = AuthorizedSignatories::findOrFail($id);

        $issuedQuotation = $signatory->issuedQuotation;
        $quotation = $issuedQuotation?->quotation;

        $user = Auth::user();

        $isAuthorized =
            $user->id === $issuedQuotation?->issued_by ||
            $user->hasRole('Lead Account Specialist') ||
            $user->id === $quotation?->as_id ||
            $user->id === $quotation?->created_by;

        if (! $isAuthorized) {
            return $this->error('You are not authorized to view this signature file', 403);
        }

        return Storage::disk('local')->response($signatory->signature_file_path);
    }
}
