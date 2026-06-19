<?php

namespace App\Http\Controllers\PlanningTimeline;

use App\Exceptions\InvalidConfigIdsException;
use App\Exceptions\VersionConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlanningTimeline\StorePlanningTemplateRequest;
use App\Http\Requests\PlanningTimeline\UpdatePlanningTemplateRequest;
use App\Http\Requests\PlanningTimeline\UpdateTemplatePhaseHeadingRequest;
use App\Http\Resources\PlanningTimeline\Template\PlanningPhaseHeadingResource;
use App\Http\Resources\PlanningTimeline\Template\PlanningTemplateResource;
use App\Models\PlanningTimeline\Template\PlanningTemplate;
use App\Models\PlanningTimeline\Template\PlanningTemplatePhase;
use App\Models\ServiceType;
use App\Services\PlanningConfigService;
use App\Services\PlanningTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PlanningTemplateController extends Controller
{
    public function __construct(
        private readonly PlanningTemplateService $templateService, 
        private readonly PlanningConfigService $configService,
    ) {
        $this->authorizeResource(PlanningTemplate::class, 'template', [
            'except' => ['destroy'],
        ]);
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
        $validated = $request->validated();

        $serviceType = ServiceType::find($validated['service_type_id']);
        $templateConfig = $this->configService->getTemplateConfig($serviceType->service);

        if (!$this->configService->isValidConfigVersion($templateConfig, $validated['config_version_number'])) {
            throw new VersionConflictException([
                'config_version_number' => 'your changes are based on an old planning template Configuration version. Reload and try again.'
            ]);
        }

        $this->templateService->validateConfigIds($validated);

        $planningTemplate = $this->templateService->create($validated, $serviceType);
        $planningTemplate = $this->templateService->loadForView($planningTemplate);

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
        $template = $this->templateService->loadForView($template);

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
    public function update(UpdatePlanningTemplateRequest $request, PlanningTemplate $template)
    {
        $validated = $request->validated();

        $conflictViolations = [];

        if ($validated['template_version_number'] !== $template->version_number) {
            $conflictViolations[] = [
                "template_version_number" => "Your changes are based on an old Planning Template version. Reload and try again."
            ]; 
        }

        $templateConfig = $this->configService->getTemplateConfig($template->service_category);
        if (!$this->configService->isValidConfigVersion($templateConfig, $validated['config_version_number'])) {
            $conflictViolations[] = [
                "config_version_number" => "Your changes are based on an old Template Configuration version. Reload and try again."
            ]; 
        }

        if (!empty($conflictViolations)) {
            throw new VersionConflictException($conflictViolations);
        }

        $this->templateService->validateConfigIds($validated);
        $template = $this->templateService->update($template, $validated);
        $template = $this->templateService->loadForView($template);

        return $this->success(
            'Planning Template updated successfully',
            new PlanningTemplateResource($template)
        );
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
        $this->authorize('toggleStatus', $template);

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

    /**
     * Update Template Phase Headings
     * 
     * Change the headings of a phase in a planning template.
     */
    public function updateHeadings(UpdateTemplatePhaseHeadingRequest $request, PlanningTemplate $template, PlanningTemplatePhase $phase) {
        $this->authorize('updateHeadings', [$template, $phase]);

        $validated = $request->validated();

        $phaseHeadings = DB::transaction(function() use ($phase, $validated) {
            $incomingHeadingIds = collect($validated['headings'])->pluck('id')->filter()->all();
            $phase->headings()->customs()->whereNotIn('id', $incomingHeadingIds)->delete();

            $headingsToUpdate = collect($validated['headings'])
                ->filter(fn($h)  => $h['id'] ?? [])
                ->all();

            $defaultHeadingIds = $phase->headings()->defaults()->pluck('id')->all();

            foreach ($headingsToUpdate as $headingData) {
                if (in_array($headingData['id'], $defaultHeadingIds)) {
                    $phase->headings()->where('id', $headingData['id'])->update([
                        'sort_order' => $headingData['sort_order']
                    ]);

                    continue;
                } 

                $phase->headings()->where('id', $headingData['id'])->update([
                    'name' => $headingData['name'],
                    'input_type' => $headingData['input_type'],
                    'sort_order' => $headingData['sort_order']
                ]);
            }

            $headingsToCreate = collect($validated['headings'])
                ->filter(fn($h) => ($h['id'] ?? null) === null)
                ->values()
                ->all();

            $phase->headings()->createMany($headingsToCreate);

            return $phase->headings;
        });

        return $this->success(
            'Phase headings updated successfully',
            PlanningPhaseHeadingResource::collection($phaseHeadings)
        );
    }
}
