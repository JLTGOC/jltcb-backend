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
        //
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