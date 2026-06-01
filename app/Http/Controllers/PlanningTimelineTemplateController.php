<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlanningTimeline\PlanningTemplateResource;
use App\Models\PlanningTimeline\Template\PlanningTemplate;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class PlanningTimelineTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'filter.service' => 'sometimes|nullable|string|in:LOGISTICS,REGULATORY'
        ]);

        $planningTemplates = QueryBuilder::for(PlanningTemplate::class)
            ->allowedFilters([AllowedFilter::exact('service', 'service_category')])
            ->get();

        return $this->success(
            'Planning Templates fetched successfully',
            PlanningTemplateResource::collection($planningTemplates),
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
     * Display the specified resource.
     */
    public function show(PlanningTemplate $template)
    {
        $template->load([
            'configPhases',
            'configProcesses',
            'configTasks',
            'phases.configPhase:id,name',
            'phases.processes.configProcess:id,name',
            'phases.processes.tasks.configTask:id,name',
        ]);

        return $this->success(
            'Planning Templates fetched successfully',
            new PlanningTemplateResource($template),
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
}
