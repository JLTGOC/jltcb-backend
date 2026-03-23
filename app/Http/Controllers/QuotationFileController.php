<?php

namespace App\Http\Controllers;

use App\Http\Resources\QuotationFileResource;
use App\Models\{
    QuotationFile,
    Quotation
};
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
     * Update Client Documents
     * 
     * Updates client documents only and directly.
     */
    public function update(Request $request, Quotation $quotation, QuotationFile $file)
    {
        $request->validate([
            'file_name' => 'required|string|max:255'
        ]);

        $file->update([
            'original_file_name' => $request->file_name . '.' . $file->file_type
        ]);
        
        return $this->success('File updated successfully', new QuotationFileResource($file));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
