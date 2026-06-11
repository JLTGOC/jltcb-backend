<?php

namespace App\Http\Controllers\PlanningTimeline;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanningTimeline\Template\PlanningTemplateResource;
use App\Models\PlanningTimeline\Template\PlanningTemplate;
use App\Http\Requests\PlanningTimeline\StorePlanningTemplateRequest;
use App\Services\PlanningTemplateService;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PlanningTemplateController extends Controller
{
    public function __construct(private readonly PlanningTemplateService $planningTemplateService) {
        //
    }

    /**
     * Index Planning Templates
     * 
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'filter.service' => 'sometimes|nullable|string|in:LOGISTICS,REGULATORY',
            'filter.is_active' => 'sometimes|nullable|boolean'
        ]);

        $planningTemplates = QueryBuilder::for(PlanningTemplate::class)
            ->allowedFilters([
                AllowedFilter::exact('service', 'service_category'),
                AllowedFilter::exact('is_active'),
            ])
            ->get();

        return $this->success(
            'Planning Templates fetched successfully',
            PlanningTemplateResource::collection($planningTemplates),
        );
    }

    /**
     * Store Planning Templates
     * 
     * Store a newly created resource in storage.
     */
    public function store(StorePlanningTemplateRequest $request)
    {
        $planningTemplate = $this->planningTemplateService->create($request->validated());

        $planningTemplate = $this->planningTemplateService->loadForView($planningTemplate);
        
        return $this->success(
            'Planning Template created successfully',
            new PlanningTemplateResource($planningTemplate)
        );
    }

    /**
     * Show Planning Templates
     * 
     * Display the specified resource.
     */
    public function show(PlanningTemplate $template)
    {
        $template = $this->planningTemplateService->loadForView($template);

        return $this->success(
            'Planning Templates fetched successfully',
            new PlanningTemplateResource($template),
        );
    }

    /**
     * Update Planning Template
     * 
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
     * Toggle Template Active Status
     * 
     * Change the active status of a planning template.
     */
    public function toggleStatus(Request $request, PlanningTemplate $template)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $template->update([
            'is_active' => $request->is_active
        ]);

        return $this->success(
            'Planning Template active status changed successfully', 
            new PlanningTemplateResource($template)
        );
    }
}
