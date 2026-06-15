<?php

namespace App\Http\Controllers\PlanningTimeline;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlanningTimeline\UpdatePlanningConfigRequest;
use App\Http\Resources\PlanningTimeline\Config\PlanningTemplateConfigResource;
use App\Models\PlanningTimeline\Config\PlanningTemplateConfig;
use App\Services\PlanningConfigService;
use App\Exceptions\VersionConflictException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlanningConfigController extends Controller
{
    public function __construct(private readonly PlanningConfigService $configService) {
        //
    }

    /**
     * Show Planning Template Configs
     * 
     * NOTE: serviceCategory should be either 'LOGISTICS' or 'REGULATORY'
     */
    public function show(string $serviceCategory)
    {
        $templateConfig = $this->configService->getTemplateConfig($serviceCategory);

        $templateConfig = $this->configService->loadForView($templateConfig);

        return $this->success(
            'Planning Template configs for ' . $serviceCategory . ' fetched successfully',
            new PlanningTemplateConfigResource($templateConfig)
        );
    }

    /**
     * Update Planning Template 
     * 
     * NOTE: serviceCategory should be either 'LOGISTICS' or 'REGULATORY'
     */
    public function update(UpdatePlanningConfigRequest $request, string $serviceCategory) {
        $validated = $request->validated();

        $templateConfig = $this->configService->getTemplateConfig($serviceCategory);

        if (!$this->configService->isValidConfigVersion($templateConfig, $validated['version_number'])) {
            throw new VersionConflictException([
                'version_number' => 'Your changes are based on an old template configuration version. Reload and try again.'
            ]);
        }

        $configIdsValidator = Validator::make($validated, [
            "phases.*.id"    => 'exists:planning_config_phases,id',
            'processes.*.id' => 'exists:planning_config_processes,id',
            'tasks.*.id'     => 'exists:planning_config_tasks,id',
        ]);

        if ($configIdsValidator->fails()) {
            return $this->error(
                message: 'The selected template configuration ids are invalid',
                data: $configIdsValidator->errors(),
                statusCode: 422
            );
        }

        $newTemplateConfig = $this->configService->update($templateConfig, $validated);

        return $this->success(
            $serviceCategory . ' Template configurations updated successfully', 
            new PlanningTemplateConfigResource($newTemplateConfig),
        );
    }
}
