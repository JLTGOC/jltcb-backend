<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIssuedQuotationRequest;
use App\Http\Requests\UpdateIssuedQuotationRequest;
use App\Http\Resources\IssuedQuotationResource;
use App\Models\AuthorizedSignatories;
use App\Models\IssuedQuotation;
use App\Models\Quotation;
use App\Models\QuotationFile;
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

        $issuedQuotation = DB::transaction(function() use ($quotation, $request, $asId) {
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

            $signatureFile = $request->file('signatory.signature_file');
            $signatureFilePath = $signatureFile->store('signatures', 'local');

            $issuedQuotation->authorizedSignatory()->create([
                'closing_statement' => $request->input('signatory.closing_statement'),
                'is_authorized_signatory' => $request->input('signatory.is_authorized_signatory'),
                'authorized_signatory_name' => $request->input('signatory.authorized_signatory_name'),
                'position' => $request->input('signatory.position'),
                'signature_file_path' => $signatureFilePath
            ]);

            $quotationFile = $request->file('issued_quotation_file');
            $filePath = $quotationFile->store('files', 'local');

            $quotation->files()->create([
                'file_path' => $filePath, 
                'uploaded_by' => $asId, 
                'type' => 'PROPOSAL', 
                'original_file_name' => $quotationFile->getClientOriginalName(), 
                'file_type' => $quotationFile->getClientOriginalExtension()
            ]);

            $quotation->update(['status' => 'RESPONDED', 'created_by' => $asId]);
            return $issuedQuotation;
        });

        $issuedQuotation->load([
            'detailValues', 'charges.items', 'authorizedSignatory', 'standardConfig', 'template.quotationFields', 'quotation.files'
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
    
        DB::transaction(function() use ($request, $quotation, $issuedQuotation) {
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
                    'subtotal'     => collect($charge['items'])->sum('amount'),
                ]);

                $chargeRecord->items()->createMany($charge['items']);
            }

            $issuedQuotation->standardConfig()->delete();
            $issuedQuotation->standardConfig()->create($request->standard_config);

            $signatory = $issuedQuotation->authorizedSignatory;
            $signatureFilePath = $signatory->signature_file_path;

            if ($request->hasFile('signatory.signature_file')) {
                Storage::disk('local')->delete($signatureFilePath);
                $signatureFilePath = $request->file('signatory.signature_file')->store('signatures', 'local');
            }

            $signatory->update([
                'closing_statement'         => $request->input('signatory.closing_statement'),
                'is_authorized_signatory'   => $request->input('signatory.is_authorized_signatory'),
                'authorized_signatory_name' => $request->input('signatory.authorized_signatory_name'),
                'position'                  => $request->input('signatory.position'),
                'signature_file_path'       => $signatureFilePath,
            ]);

            $quotation->files()->where('type', 'PROPOSAL')->delete();

            $quotationFile = $request->file('issued_quotation_file');
            $filePath = $quotationFile->store('files', 'local');

            $quotation->files()->create([
                'file_path' => $filePath, 
                'uploaded_by' => Auth::user()->id, 
                'type' => 'PROPOSAL', 
                'original_file_name' => $quotationFile->getClientOriginalName(), 
                'file_type' => $quotationFile->getClientOriginalExtension()
            ]);
        });

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
    public function serveSignature($id)
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
