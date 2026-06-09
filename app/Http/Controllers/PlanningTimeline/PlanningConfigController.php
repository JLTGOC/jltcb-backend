<?php

namespace App\Http\Controllers\PlanningTimeline;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlanningTimeline\UpdatePlanningConfigRequest;
use App\Models\PlanningTimeline\Config\PlanningConfigPhase;
use App\Models\PlanningTimeline\Config\PlanningConfigProcess;
use App\Models\PlanningTimeline\Config\PlanningConfigTask;
use App\Http\Resources\PlanningTimeline\Config\PlanningTemplateConfigResource;
use App\Models\PlanningTimeline\Config\PlanningTemplateConfig;
use App\Services\PlanningConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlanningConfigController extends Controller
{
    public function __construct(private readonly PlanningConfigService $planningConfigService) {
        //
    }

    /**
     * Show Planning Template Configs
     * 
     * NOTE: serviceCategory should be either 'LOGISTICS' or 'REGULATORY'
     */
    public function show(string $serviceCategory)
    {

        $configVersion = PlanningTemplateConfig::with([
            'phases.templatePhases.template', 
            'processes.templateProcesses.phase.template', 
            'tasks.templateTasks.process.phase.template',

            'phases' => fn ($q) => $q->withExists([
                'templatePhases as is_locked'
            ]),
            'processes' => fn ($q) => $q->withExists([
                'templateProcesses as is_locked'
            ]),
            'tasks' => fn ($q) => $q->withExists([
                'templateTasks as is_locked'
            ]),
        ])->where('service_category', $serviceCategory)->firstOrFail();

           return $this->success(
            'Planning Template configs for ' . $serviceCategory . ' fetched successfully',
            new PlanningTemplateConfigResource($configVersion)
        );
    }

    /**
     * Update Planning Template 
     * 
     * NOTE: serviceCategory should be either 'LOGISTICS' or 'REGULATORY'
     */
    public function update(UpdatePlanningConfigRequest $request, string $serviceCategory) {
        $validated = $request->validated();

        $templateConfig = PlanningTemplateConfig::where('service_category', $serviceCategory)
            ->first();

        if ($validated['version_number'] !== $templateConfig->version_number) {
            return $this->error(
                'Version Conflict: Your changes are based on an old Planning Template Configuration version. Reload and try again.', 
                statusCode: 409
            );               
        }

        $newTemplateConfig = $this->planningConfigService->update($templateConfig, $validated);

        return $this->success(
            $serviceCategory . ' template configurations fetched successfully', 
            new PlanningTemplateConfigResource($newTemplateConfig)
        );
    }
}
