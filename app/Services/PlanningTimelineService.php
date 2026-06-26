<?php

namespace App\Services;

use App\Models\JobOrder;
use App\Models\PlanningTimeline\Timeline\Timeline;
use App\Models\PlanningTimeline\Timeline\TimelinePhaseHeading;
use App\Models\PlanningTimeline\Timeline\TimelineTask;
use App\Models\PlanningTimeline\Timeline\TimelineTaskValue;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PlanningTimelineService {
    public function create(JobOrder $jobOrder, array $data, User $createdBy) {
        $timeline = DB::transaction(function() use ($jobOrder, $data, $createdBy) {
            $timeline = $jobOrder->timeline()->create([
                'planning_template_id' => $data['planning_template_id'] ?? null,
                'created_by' => $createdBy->id
            ]);

            foreach($data['phases'] as $phaseData) {
                $timelinePhase = $timeline->phases()->create([
                    'name' => $phaseData['name'],
                    'sort_order' => $phaseData['sort_order']
                ]);

                $this->insertHeadings($timelinePhase->id, $phaseData['headings']);

                foreach($phaseData['processes'] as $processData) {
                    $timelineProcess = $timelinePhase->processes()->create([
                        'name' => $processData['name']
                    ]);

                    foreach($processData['tasks'] as $taskData) {
                        $timelineTask = $timelineProcess->tasks()->create([
                            'name' => $taskData['name']
                        ]);

                        $timelineHeadings = $timelinePhase->headings;

                        foreach($timelineHeadings as $heading) {
                            $timelineTask->values()->create([
                                'timeline_phase_heading_id' => $heading->id
                            ]);
                        }
                    }
                }
            }

            return $timeline;
        });

        return $this->loadForView($timeline);
    }

    public function loadForView(Timeline $timeline) {
        return $timeline->load([
            'phases.processes.tasks.values.phaseHeading',
            'phases.processes.tasks.assignees',
            'phases.headings'
        ]);
    }

    public function assignTasks(Timeline $timeline, array $data) {
        $submittedTaskIds = collect($data['assignments'])->pluck('task_id')->all();

        $currentTasks = TimelineTask::with(['process.phase.headings'])->whereIn('id', $submittedTaskIds)->get()->keyBy('id');

        DB::transaction(function() use ($data, $currentTasks) {
            foreach($data['assignments'] as $assignment) {
                $task = $currentTasks->get($assignment['task_id']);
                
                $task->assignees()->sync($assignment['user_ids']);

                $targetDateHeading = $task->process->phase->headings->firstWhere('key', 'target_datetime');
                
                TimelineTaskValue::updateOrCreate(
                    [
                        'timeline_task_id' => $task->id,
                        'timeline_phase_heading_id' => $targetDateHeading->id
                    ],
                    [
                        'value' => $assignment['target_datetime']
                    ]
                );
                
            }
        });

        $timeline = $this->loadForView($timeline);
        return $timeline;
    }

    private function insertHeadings(int $phaseId, array $headings) {
        $rows = collect($headings)->map(function($heading) use ($phaseId, $headings) {
            return [
                'timeline_phase_id' => $phaseId,
                'key'               => $heading['key'] ?? null,
                'input_type'        => $heading['input_type'],
                'name'              => $heading['name'],
                'sort_order'        => $heading['sort_order']
            ];
        })->toArray();

        TimelinePhaseHeading::insert($rows);
    }
}