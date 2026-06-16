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
use Illuminate\Support\Facades\Validator;

class PlanningTemplateService {
    public function __construct(private readonly PlanningConfigService $planningConfigService) {

    }

    public function create(array $data, ServiceType $serviceType) : PlanningTemplate {
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

    public function update(PlanningTemplate $template, array $data) {
        $template = $this->loadForView($template);

        return DB::transaction(function() use ($template, $data) {
            $incomingPhaseIds = collect($data['phases'])->pluck('config_phase_id')->all();
            $template->phases()->whereNotIn('config_phase_id', $incomingPhaseIds)->delete();

            $headingsToInsert = [];
            
            foreach ($data['phases'] ?? [] as $phaseData) {

                $phase = $template->phases()->updateOrCreate(
                    [
                        "planning_template_id" => $template->id,
                        'config_phase_id' => $phaseData['config_phase_id']
                    ],
                    [
                        "sort_order" => $phaseData['sort_order']
                    ]
                );

                if ($phase->wasRecentlyCreated) {
                    foreach (DefaultPhaseHeading::defaultRows($phase->id) as $record) {
                        $headingsToInsert[] = $record;
                    }
                }

                $incomingProcessIds = collect($phaseData['processes'])->pluck('config_process_id')->all();
                $phase->processes()->whereNotIn('config_process_id', $incomingProcessIds)->delete();
                
                foreach ($phaseData['processes'] ?? [] as $processData) {
                    $process = $phase->processes()->firstOrCreate([
                        'config_process_id' => $processData['config_process_id']
                    ]);

                    $incomingTaskIds = collect($processData['tasks'] ?? [])->pluck('config_task_id')->all();
                    $process->tasks()->whereNotIn('config_task_id', $incomingTaskIds)->delete();

                    $currentTaskIds = $process->tasks()->pluck('config_task_id')->all();
                    $tasksToCreate = array_diff($incomingTaskIds, $currentTaskIds);

                    foreach ($tasksToCreate as $taskId) {
                        $process->tasks()->create([
                            'config_task_id' => $taskId
                        ]);
                    }
                }
            }

            if (!empty($headingsToInsert)) {
                PlanningTemplatePhaseHeading::insert($headingsToInsert);
            }

            return $template;
        });
    }

    public function validateConfigIds(array $data) {
        $configIdsValidator = Validator::make($data, [
            "phases.*.config_phase_id" => 'exists:planning_config_phases,id',
            "phases.*.processes.*.config_process_id" => 'exists:planning_config_processes,id',
            "phases.*.processes.*.tasks.*.config_task_id" => 'exists:planning_config_tasks,id',
        ]);

        if ($configIdsValidator->fails()) {
            throw new InvalidConfigIdsException($configIdsValidator->errors()->toArray());
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