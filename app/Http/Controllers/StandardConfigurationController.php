<?php

namespace App\Http\Controllers;

use App\Http\Resources\StandardConfigResource;
use App\Models\QuotationTemplateConfig\StandardConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StandardConfigurationController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(StandardConfiguration::class, 'template', [
            'except' => ['show', 'update', 'destroy']
        ]);
    }

    /**
     * Index standard quotation templates
     * 
     * Display a listing of the resource.
     */
    public function index()
    {
        $standardTemplates = StandardConfiguration::all();

        $message =  $standardTemplates->isEmpty() 
            ? 'No Standard Quotation template available' 
            : 'Standard Quotation templates fetched successfully';

        return $this->success($message, StandardConfigResource::collection($standardTemplates));
    }

    /**
     * Store standard quotation templates
     * 
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'template_name' => [
                'required', 'string', 'max:255', 'unique:standard_configurations,template_name'
            ],
            'policies' => ['required', 'string'],
            'terms_and_conditions' => ['required', 'string'],
            'banking_details' => ['required', 'string'],
            'footer' => ['required', 'string', 'max:255'],
        ]);

        $configuration = DB::transaction(function () use ($validated) {
            return StandardConfiguration::create($validated);
        });

        return $this->success('Standard Quotation template stored successfully', new StandardConfigResource($configuration), 201);
    }

    /**
     * Show standard quotation templates
     * 
     * Display the specified resource.
     */
    public function show(StandardConfiguration $template)
    {
        $this->authorize('view', $template);

        return $this->success('Standard Quotation template fetched successfully', new StandardConfigResource($template));
    }

    /**
     * Update standard quotation templates
     * 
     * Update the specified resource in storage.
     */
    public function update(Request $request, StandardConfiguration $template)
    {
        $this->authorize('update', $template);

        $validated = $request->validate([
            'template_name' => [
                'sometimes', 'required', 'string', 'max:255', 
                Rule::unique('standard_configurations', 'template_name')->ignore($template),
            ],
            'policies' => ['sometimes' ,'required', 'string'],
            'terms_and_conditions' => ['sometimes', 'required', 'string'],
            'banking_details' => ['sometimes', 'required', 'string'],
            'footer' => ['sometimes' ,'required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($template, $validated) {
            $template->update($validated);
        });

        return $this->success('Standard Quotation template updated successfully', new StandardConfigResource($template));
    }

    /**
     * Delete standard quotation templates
     * 
     * Remove the specified resource from storage.
     */
    public function destroy(StandardConfiguration $template)
    {
        $this->authorize('delete', $template);
        $template->delete();

        return $this->success('Standard Quotation Template deleted successfully');
    }
}