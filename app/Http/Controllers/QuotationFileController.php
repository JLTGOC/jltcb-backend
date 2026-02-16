<?php

namespace App\Http\Controllers;

use App\Http\Resources\QuotationFileResource;
use App\Models\QuotationFile;
use App\Models\Quotation;
use Illuminate\Http\Request;

class QuotationFileController extends Controller
{
    /**
     * Index Quotation File
     * 
     * Display a listing of the resource
     */
    public function index(Quotation $quotation, Request $request)
    {
        $this->authorize('viewAny', [QuotationFile::class, $quotation]);

        $request->validate([
            'type' => ['required', 'in:REQUESTED,PROPOSAL']
        ]);

        $quotationFiles = $quotation->files()->where('type', $request->type)->get();

        if ($quotationFiles->isEmpty()) {
            return $this->success('No files available');
        }

        return $this->success(
            'Files retrieved successfully.', QuotationFileResource::collection($quotationFiles)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show Quotation File
     * 
     * Display the details of the quotation file.
     */
    public function show(Quotation $quotation, QuotationFile $file)
    {
        $this->authorize('view', [QuotationFile::class, $quotation, $file]);

        return $this->success('File retrieved sucessfully', new QuotationFileResource($file));
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
}
