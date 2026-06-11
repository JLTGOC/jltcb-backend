<?php

namespace App\Services;

use App\Enums\DefaultPhaseHeading;
use App\Exceptions\InvalidConfigIdsException;
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

        $templateConfig = $this->planningConfigService
            ->findAndValidateTemplateConfig($serviceType->service, $data['config_version_number']);

        $templateConfig->load(['phases', 'processes', 'tasks']);

        $this->assertConfigIdsAreValid($data['phases'], $templateConfig);

        return DB::transaction(function() use ($data, $serviceType) {
            $planningTemplate = PlanningTemplate::create([
                'name' => $data['name'], 
                'service_category' => $serviceType->service, 
                'service_type_id' => $serviceType->id, 
                'version_number' => 1,
                'is_active' => true
            ]);

            $headings = [];

            foreach($data['phases'] as $phaseData) {
                $templatePhase = $planningTemplate->phases()->create([
                    'config_phase_id' => $phaseData['config_phase_id'], 
                    'sort_order' => $phaseData['config_phase_id']
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
                PlanningTemplatePhaseHeading::insert($headings);
            }

            return $planningTemplate;
        });
    }

    private function assertConfigIdsAreValid(array $phases, PlanningTemplateConfig $planningTemplateConfig) {
        $validPhaseIds = $planningTemplateConfig->phases->pluck('id')->all();
        $validProcessIds = $planningTemplateConfig->phases->pluck('id')->all();
        $validTaskIds = $planningTemplateConfig->phases->pluck('id')->all();

        $submittedPhaseIds = collect($phases)->pluck('config_phase_id')->all();

        $submittedProcessIds = collect($phases)
            ->flatMap(fn($phase) => collect($phase['processes'])->pluck('config_process_id'))
            ->unique()
            ->all();

        $submittedTaskIds = collect($phases)
            ->flatMap(fn($phase) => collect($phase['processes']))
                ->flatMap(fn($process) => collect($process['tasks'])->pluck('config_task_id'))
            ->unique()
            ->values()
            ->all();

        $invalidPhases    = array_diff($submittedPhaseIds,   $validPhaseIds);
        $invalidProcesses = array_diff($submittedProcessIds, $validProcessIds);
        $invalidTasks     = array_diff($submittedTaskIds,    $validTaskIds);

        if ($invalidPhases || $invalidProcesses || $invalidTasks) {
            throw new InvalidConfigIdsException([
                'invalid_phases' => $invalidPhases,
                'invalid_processes' => $invalidProcesses,
                'invalid_tasks' => $invalidTasks
            ]);
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
}