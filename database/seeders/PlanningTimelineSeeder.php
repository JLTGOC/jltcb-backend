<?php

namespace Database\Seeders;

use App\Enums\DefaultPhaseHeading;
use App\Models\JobOrder;
use App\Models\PlanningTimeline\Template\PlanningTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanningTimelineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $acceptedJobOrders = JobOrder::whereNotNull('operations_id')->get();

        foreach($acceptedJobOrders as $jobOrder) {
            $hasTimeline = fake()->boolean();

            if (!$hasTimeline) {
                continue;
            }

            $serviceCategory = $jobOrder->quotation->serviceCategory();
            $template = PlanningTemplate::where('service_category', $serviceCategory)->inRandomOrder()->first();

            $timeline = $jobOrder->timeline()->create([
                'created_by'           => $jobOrder->operations_id,
                'planning_template_id' => $template->id
            ]);

            $baseDate = Carbon::now()->addMonth();

            $templatePhases = $template->phases->sortBy('sort_order');

            foreach($templatePhases as $phase) {
                $phaseStartDate = $baseDate;

                $timelinePhase = $timeline->phases()->create([
                    'name'       => $phase->configPhase->name,
                    'sort_order' => $phase->sort_order
                ]);

                $templatePhaseHeadings = $phase->headings;
                $timelinePhaseHeadings = $templatePhaseHeadings->map(function($heading) use ($timelinePhase) {
                    return [
                        'timeline_phase_id' => $timelinePhase->id,
                        'name' => $heading->name,
                        'key' => $heading->key,
                        'sort_order' => $heading->sort_order,
                        'input_type' => $heading->input_type
                    ];
                })->toArray();

                $timelinePhase->headings()->insert($timelinePhaseHeadings);

                $taskDateCursor = $phaseStartDate;

                $templateProcesses = $phase->processes;

                foreach($templateProcesses as $process) {
                    $timelineProcess = $timelinePhase->processes()->create([
                        'name' => $process->configProcess->name
                    ]);

                    $templateTasks = $process->tasks;

                    foreach($templateTasks as $task) {
                        $taskDateCursor = $taskDateCursor->copy()->addDays(rand(0, 2));

                        $timelineTask = $timelineProcess->tasks()->create([
                            'name' => $task->configTask->name
                        ]);

                        $csds = User::role('Client Success')->pluck('id')->all();
                        $randomCsdCount = fake()->numberBetween(1, 2);
                        $randomCsd = fake()->randomElements($csds, $randomCsdCount);

                        $timelineTask->assignees()->attach($randomCsd);

                        $timelinePhaseHeadings = $timelinePhase->headings;

                        foreach ($timelinePhaseHeadings as $heading) {
                            if ($heading->key === 'target_datetime') {
                                $timelineTask->values()->create([
                                    'timeline_phase_heading_id' => $heading->id,
                                    'value' => $taskDateCursor->toDateTimeString(),
                                ]);

                                continue;
                            }

                            $timelineTask->values()->create([
                                'timeline_phase_heading_id' => $heading->id,
                            ]);
                        }
                    }
                }

                $baseDate = $taskDateCursor->copy()->addDays(rand(1, 3));
            }
        }
    }
}
