<?php

namespace App\Http\Controllers\PlanningTimeline;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanningTimeline\Timeline\TimelineDocumentResource;
use App\Models\PlanningTimeline\Timeline\Timeline;
use App\Models\PlanningTimeline\Timeline\TimelineDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TimelineDocumentController extends Controller
{
    /**
     * Index Timeline Documents
     * 
     */
    public function index(Timeline $timeline)
    {
        $this->authorize('viewAllDocuments', [Timeline::class, $timeline]);

        $documents = $timeline->documents->load('uploadedBy');

        return $this->success(
            'Planning timeline documents fetched successfully', 
            TimelineDocumentResource::collection($documents)
        );
    }

    /**
     * Store Timeline Documents
     * 
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Timeline $timeline)
    {
        $this->authorize('uploadDocument', [Timeline::class, $timeline]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100', 'in:INVOICE,PL,BL,AWB,CERTIFICATE,LICENSE,INSURANCE,NOTICE'],
            'file' => ['required', 'file', 'max:5120', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,heic',],
        ]);

        $file = $request->file('file')->store('timeline/files', 'local');
        $fileExtension = $request->file('file')->getClientOriginalExtension();

        $document = $timeline->documents()->create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'status' => 'UPLOADED',
            'uploaded_by' => $request->user()->id,
            'file_type' => $fileExtension,
            'file_path' => $file
        ]);

        $document->load('uploadedBy');

        return $this->success(
            'Planning Timeline document stored successfully',
            new TimelineDocumentResource($document)
        );
    }

    /**
     * Show Timeline Documents
     * 
     * Display the specified resource.
     */
    public function show(Timeline $timeline, TimelineDocument $document)
    {
        $this->authorize('viewDocumentData', [Timeline::class, $timeline, $document]);

        $document->load('uploadedBy');

        return $this->success(
            'Planning Timeline document stored successfully',
            new TimelineDocumentResource($document)
        );
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
     * File Type Enums
     */
    public function availableFileTypes()
    {
        $this->authorize('availableFileTypes', [Timeline::class]);

        return $this->success(
            'Available timeline document file types fetched successfully', 
            ['INVOICE', 'PL', 'BL', 'AWB', 'CERTIFICATE', 'LICENSE', 'INSURANCE', 'NOTICE']
        );
    }

    /**
     * View Timeline Document
     */
    public function viewDocument(TimelineDocument $document)
    {
        $this->authorize('viewDocument', [Timeline::class, $document]);

        return Storage::disk('local')->response($document->file_path);
    }

    
}
