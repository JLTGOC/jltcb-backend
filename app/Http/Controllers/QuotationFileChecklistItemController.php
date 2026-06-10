<?php

namespace App\Http\Controllers;

use App\Models\QuotationFileChecklistItem;
use Illuminate\Http\Request;

class QuotationFileChecklistItemController extends Controller
{
    /**
     * Index Document Checklist Items
     * 
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'sometimes|nullable|string',
            'visibility' => 'sometimes|nullable|in:LOGISTICS,REGULATORY,BOTH',
        ]);

        $query = QuotationFileChecklistItem::query();

        if (isset($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if (isset($request->visibility)) {
            $query->where('visibility', $request->visibility);
        }

        $checklistItems = $query->get();

        return $this->success('Checklist items retrieved successfully', $checklistItems);
    }

    /**
     * Store Document Checklist Item
     * 
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:quotation_file_checklist_items,name',
            'visibility' => 'required|in:LOGISTICS,REGULATORY,BOTH'
        ]);

        $checklistItem = QuotationFileChecklistItem::create($request->all());

        return $this->success('Checklist item created successfully', $checklistItem, 200);
    }

    /**
     * Show Document Checklist Item
     * 
     * Display the specified resource.
     */
    public function show(QuotationFileChecklistItem $quotationFileChecklistItem)
    {
        //
    }

    /**
     * Update Document Checklist Item
     * 
     * Update the specified resource in storage.
     */
    public function update(Request $request, QuotationFileChecklistItem $quotationFileChecklistItem)
    {
        $request->validate([
            'name' => 'sometimes|nullable|string|unique:quotation_file_checklist_items,name,' . $quotationFileChecklistItem->id,
            'visibility' => 'sometimes|nullable|in:LOGISTICS,REGULATORY,BOTH'
        ]);

        $quotationFileChecklistItem->update($request->all());

        return $this->success('Checklist item updated successfully', $quotationFileChecklistItem, 200);
    }

    /**
     * Delete Document Checklist Item
     * 
     * Remove the specified resource from storage.
     */
    public function destroy(QuotationFileChecklistItem $quotationFileChecklistItem)
    {
        $quotationFileChecklistItem->delete();

        return $this->success('Checklist item deleted successfully', null, 200);
    }
}
