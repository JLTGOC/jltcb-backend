<?php

namespace App\Http\Controllers\PlanningTimeline;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlanningTimeline\UpdatePlanningConfigRequest;
use App\Http\Resources\PlanningTimeline\PlanningConfigResource;
use App\Models\PlanningTimeline\Config\PlanningConfigPhase;
use App\Models\PlanningTimeline\Config\PlanningConfigProcess;
use App\Models\PlanningTimeline\Config\PlanningConfigTask;
use App\Models\PlanningTimeline\Config\PlanningConfigVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlanningConfigController extends Controller
{
    /**
     * Show Planning Template Configs
     * 
     * NOTE: serviceCategory should be either 'logistics' or 'regulatory'
     */
    public function show(string $serviceCategory)
    {

        $configVersion = PlanningConfigVersion::with(['phases', 'processes', 'tasks'])
            ->where('service_category', $serviceCategory)
            ->where('is_current', true)->first();

        return $this->success(
            'Planning Template configs for ' . $serviceCategory . ' fetched successfully',
            new PlanningConfigResource($configVersion)
        );
    }

    /**
     * Update Planning Template Configs
     */
    public function update(UpdatePlanningConfigRequest $request, string $serviceCategory)
    {
        $validated = $request->validated();

        $currentVersion = PlanningConfigVersion::where('service_category', $serviceCategory)
            ->where('is_current', true)
            ->first();

        if ($currentVersion->id !== $validated['version_id']) {
            return $this->error('Version Conflict: Your changes are based on an old Planning Template Configuration version. Reload and try again.', statusCode: 409);
        }

        $locked = PlanningConfigVersion::where('id', $currentVersion?->id)->where('is_current', true)->update([
            'is_current' => null
        ]);

        if ($locked === 0) {
            return $this->error(
                'This configuration was updated by another user while you were editing. Please reload and reapply your changes.', statusCode: 409
            );
        }

        DB::transaction(function() use ($validated, $serviceCategory, $currentVersion) {
            $newConfigVersion = PlanningConfigVersion::create([
                'service_category' => $serviceCategory,
                'version_number' => $currentVersion->version_number + 1,
                'is_current' => true
            ]);

            $newVersionId = $newConfigVersion->id;

            $this->syncConfigData(PlanningConfigPhase::class, $newVersionId, $validated['phases']);
            $this->syncConfigData(PlanningConfigProcess::class, $newVersionId, $validated['processes']);
            $this->syncConfigData(PlanningConfigTask::class, $newVersionId, $validated['tasks']);
        });

        return $serviceCategory;
    }

    private function syncConfigData(string $modelClass, string $newVersionId, array $incomingData) {
        foreach ($incomingData as $data) {
            $modelClass::create([
                'config_version_id' => $newVersionId,
                'name' => $data['name']
            ]);
        }
    }
}
