<?php

namespace App\Services;

use App\Enums\DefaultPhaseHeading;
use App\Exceptions\InvalidConfigIdsException;
use App\Exceptions\VersionConflictException;
use App\Models\PlanningTimeline\Config\PlanningTemplateConfig;
use App\Models\PlanningTimeline\Template\PlanningTemplate;
use App\Models\PlanningTimeline\Template\PlanningTemplatePhaseHeading;
use App\Models\PlanningTimeline\Template\PlanningTemplateProcess;
use App\Models\PlanningTimeline\Template\PlanningTemplateTask;
use App\Models\ServiceType;
use App\Services\PlanningConfigService;
use Illuminate\Support\Facades\DB;

class PlanningTemplateService {
    public function __construct(private readonly PlanningConfigService $planningConfigService) {

    }

    public function create(array $data) : PlanningTemplate {
        $serviceType = ServiceType::find($data['service_type_id']);

        $templateConfig = PlanningTemplateConfig::with(['phases', 'processes', 'tasks'])
            ->where('service_category', $serviceType->service)->first();

        if (!$this->planningConfigService->isValidConfigVersion($templateConfig, $data['config_version_number'])) {
            throw new VersionConflictException([
                'config_version_number' => 'your changes are based on an old planning template Configuration version. Reload and try again.'
            ]);
        }

        $this->assertConfigIdsAreValid($data['phases'], $templateConfig);

        return DB::transaction(function() use ($data, $serviceType) {
            $planningTemplate = PlanningTemplate::create([
                'name' => $data['name'], 
                'service_category' => $serviceType->service, 
                'service_type_id' => $serviceType->id, 
                'version_number' => 1,
                'is_active' => true
            ]);

            $headingsToInsert = [];

            foreach($data['phases'] as $phaseData) {
                $templatePhase = $planningTemplate->phases()->create([
                    'config_phase_id' => $phaseData['config_phase_id'], 
                    'sort_order' => $phaseData['sort_order']
                ]);

                foreach (DefaultPhaseHeading::defaultRows($templatePhase->id) as $record) {
                    $headingsToInsert[] = $record;
                }

                foreach ($phaseData['processes'] as $processData) {
                    $templateProcess = PlanningTemplateProcess::create([
                        'template_phase_id' => $templatePhase->id,
                        'config_process_id' => $processData['config_process_id'],
                    ]);

                    foreach ($processData['tasks'] as $taskData) {
                        PlanningTemplateTask::create([
                            'template_process_id' => $templateProcess->id,
                            'config_task_id'      => $taskData['config_task_id'],
                        ]);
                    }
                }
            }

            if (!empty($headingsToInsert)) {
                PlanningTemplatePhaseHeading::insert($headingsToInsert);
            }

            return $planningTemplate;
        });
    }

    public function update(PlanningTemplate $template, $data) {
        $violations = [];

        if ($data['template_version_number'] !== $template->version_number) {
            $violations[] = [
                "template_version_number" => "Your changes are based on an old planning template version. Reload and try again."
            ]; 
        }

        $templateConfig = PlanningTemplateConfig::where('service_category', $template->service_category)->first();
        if (!$this->planningConfigService->isValidConfigVersion($templateConfig, $data['config_version_number'])) {
            $violations[] = [
                "config_version_number" => "Your changes are based on an old planning template configuration version. Reload and try again."
            ]; 
        }

        if (!empty($violations)) {
            throw new VersionConflictException($violations);
        }
    }

    public function loadForView(PlanningTemplate $template): PlanningTemplate
    {
        return $template->load([
            'serviceType',                  
            'phases.headings',                              
            'phases.processes.tasks',        
        ]);
    }

    private function assertConfigIdsAreValid(array $phases, PlanningTemplateConfig $planningTemplateConfig) {
        $validPhaseIds = $planningTemplateConfig->phases->pluck('id')->all();
        $validProcessIds = $planningTemplateConfig->processes->pluck('id')->all();
        $validTaskIds = $planningTemplateConfig->tasks->pluck('id')->all();

        $submittedPhaseIds = collect($phases)->pluck('config_phase_id')->all();

        $submittedProcessIds = collect($phases)
            ->flatMap(fn($phase) => collect($phase['processes'])->pluck('config_process_id'))
            ->unique()
            ->all();

        $submittedTaskIds = collect($phases)
            ->flatMap(fn($phase) => collect($phase['processes']))
                ->flatMap(fn($process) => collect($process['tasks'])->pluck('config_task_id'))
            ->unique()
            ->all();

        $invalidPhases = array_values(array_diff($submittedPhaseIds, $validPhaseIds));
        $invalidProcesses = array_values(array_diff($submittedProcessIds, $validProcessIds));
        $invalidTasks = array_values(array_diff($submittedTaskIds, $validTaskIds));

        if ($invalidPhases || $invalidProcesses || $invalidTasks) {
            throw new InvalidConfigIdsException([
                'invalid_phase_ids' => $invalidPhases,
                'invalid_process_ids' => $invalidProcesses,
                'invalid_task_ids' => $invalidTasks
            ]);
        }
    }
}