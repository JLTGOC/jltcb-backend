<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIssuedQuotationRequest;
use App\Http\Requests\UpdateIssuedQuotationRequest;
use App\Http\Resources\IssuedQuotationResource;
use App\Models\AuthorizedSignatories;
use App\Models\IssuedQuotation;
use App\Models\Quotation;
use App\Models\QuotationFile;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IssuedQuotationController extends Controller
{
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

        $asId = Auth::user()->id;

        $signatureFilePath = null;
        $quotationFilePath = null;

        DB::beginTransaction();

        try {
            $issuedQuotation = $quotation->issuedQuotations()->create([
                'template_id' => $request->template_id,
                'issued_by' => $asId,
                'subject' => $request->subject,
                'message' => $request->message,
            ]);

            $issuedQuotation->detailValues()->createMany($request->detail_values);

            foreach ($request->charges as $charge) {
                $chargeRecord = $issuedQuotation->charges()->create([
                    'name' => $charge['name'],
                    'subtotal' => collect($charge['items'])->sum('amount'),
                ]);

                $chargeRecord->items()->createMany($charge['items']);
            }

            $issuedQuotation->standardConfig()->create($request->standard_config);

            if ($request->hasFile('signatory.signature_file')) {
                $signatureFilePath = $request->file('signatory.signature_file')
                    ->store('signatures', 'local');
            }

            $issuedQuotation->authorizedSignatory()->create([
                'closing_statement' => $request->input('signatory.closing_statement'),
                'is_authorized_signatory' => $request->input('signatory.is_authorized_signatory'),
                'authorized_signatory_name' => $request->input('signatory.authorized_signatory_name'),
                'position' => $request->input('signatory.position'),
                'signature_file_path' => $signatureFilePath
            ]);

            if ($request->hasFile('issued_quotation_file')) {
                $quotationFile = $request->file('issued_quotation_file');

                $quotationFilePath = $quotationFile->store('files', 'local');

                $quotation->files()->create([
                    'file_path' => $quotationFilePath,
                    'uploaded_by' => $asId,
                    'type' => 'PROPOSAL',
                    'original_file_name' => $quotationFile->getClientOriginalName(),
                    'file_type' => $quotationFile->getClientOriginalExtension()
                ]);
            }

            $quotation->update([
                'status' => 'RESPONDED',
                'created_by' => $asId
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

        DB::beginTransaction();

        try {
            $issuedQuotation->update([
                'subject' => $request->subject,
                'message' => $request->message,
            ]);

            $issuedQuotation->detailValues()->delete();
            $issuedQuotation->detailValues()->createMany($request->detail_values);

            $issuedQuotation->charges()->delete();
            foreach ($request->charges as $charge) {
                $chargeRecord = $issuedQuotation->charges()->create([
                    'name' => $charge['name'],
                    'subtotal' => collect($charge['items'])->sum('amount'),
                ]);

                $chargeRecord->items()->createMany($charge['items']);
            }

            $issuedQuotation->standardConfig()->delete();
            $issuedQuotation->standardConfig()->create($request->standard_config);

            $signatory = $issuedQuotation->authorizedSignatory;

            if ($request->hasFile('signatory.signature_file')) {
                $newSignaturePath = $request->file('signatory.signature_file')->store('signatures', 'local');
            }

            $signatory->update([
                'closing_statement' => $request->input('signatory.closing_statement'),
                'is_authorized_signatory' => $request->input('signatory.is_authorized_signatory'),
                'authorized_signatory_name' => $request->input('signatory.authorized_signatory_name'),
                'position' => $request->input('signatory.position'),
                'signature_file_path' => $newSignaturePath ?? $oldSignaturePath,
            ]);

            $oldquotationFile = $quotation->files()->where('type', 'PROPOSAL')->first();
            Storage::disk('local')->delete($oldquotationFile->file_path);
            $oldquotationFile->delete();
        
            if ($request->hasFile('issued_quotation_file')) {
                $quotationFile = $request->file('issued_quotation_file');
                $newQuotationFilePath = $quotationFile->store('files', 'local');

                $quotation->files()->create([
                    'file_path' => $newQuotationFilePath,
                    'uploaded_by' => Auth::user()->id,
                    'type' => 'PROPOSAL',
                    'original_file_name' => $quotationFile->getClientOriginalName(),
                    'file_type' => $quotationFile->getClientOriginalExtension(),
                ]);
            }

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
            'detailValues', 'charges', 'authorizedSignatory', 'standardConfig', 'template.quotationFields', 'quotation.files'
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

        $quotation = $signatory->issuedQuotation;
        $user = Auth::user();

        if ($user->id === $quotation->as_id || $user->hasRole('Lead Account Specialist')) {
            return Storage::disk('local')->response($signatory->signature_file_path);
        }

        return $this->error('You are not authorized to view this signature file', 403);
    }
}
