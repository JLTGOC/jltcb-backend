<?php

namespace App\Http\Requests\PlanningTimeline;

use App\Models\ServiceType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePlanningTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string', 'max:255', 'unique:planning_templates,name'],
            'service_category'      => [
                'required', 'string', 'in:LOGISTICS,REGULATORY'],
            'service_type_id'       => [
                'bail', 'required', 'integer', 'exists:service_types,id', 
                function (string $attribute, mixed $value, Closure $fail) {
                    $serviceCategory = ServiceType::find($value)->service;
                    $requestServiceCategory = $this->service_category;
                    if ($requestServiceCategory !== $serviceCategory) {
                        $fail("The {$attribute} is not a valid service type for {$requestServiceCategory}");
                    }
                },
            ],
            'config_version_number' => ['required', 'integer'],

            'phases'                   => ['required', 'array', 'min:1'],
            'phases.*.config_phase_id' => ['required', 'integer', 'distinct'],
            'phases.*.sort_order'      => ['required', 'integer', 'min:1', 'distinct'],

            'phases.*.processes'                     => ['required', 'array', 'min:1'],
            'phases.*.processes.*.config_process_id' => ['required', 'integer'],

            'phases.*.processes.*.tasks'                   => ['required', 'array', 'min:1'],
            'phases.*.processes.*.tasks.*.config_task_id'  => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'phases.min' => 'A template must include at least one phase.',
            'phases.*.processes.min' => 'A template must include at least one process',
            'phases.*.processes.*.tasks.min' => 'A template must include at least one process',
            'phases.*.config_phase_id.required'=> 'Each phase must reference a config phase.',
            'phases.*.processes.*.config_process_id.required' => 'Each process must reference a config process.',
            'phases.*.processes.*.tasks.*.config_task_id.required' => 'Each task must reference a config task.',
        ];
    }

    public function after() {
        return [
            function (Validator $validator) {
                $sortOrders = collect($this->input('phases'))
                    ->pluck('sort_order')
                    ->sort()
                    ->values()
                    ->all();

                $expectedOrder = range(1, count($sortOrders));

                if ($sortOrders !== $expectedOrder) {
                    $validator->errors()->add(
                        'phases.*.sort_order',
                        'The sort_order values must be sequential starting from 1.'
                    );
                }

                foreach ($this->input('phases', []) as $phaseIndex => $phase) {

                    // ----------------------------
                    // Processes must be unique within phase
                    // ----------------------------
                    $processGroups = collect($phase['processes'] ?? [])
                        ->groupBy('config_process_id');

                    foreach ($processGroups as $processId => $group) {
                        if ($group->count() > 1) {

                            foreach ($group as $item) {
                                $processIndex = array_search($item, $phase['processes']);

                                $validator->errors()->add(
                                    "phases.$phaseIndex.processes.$processIndex.config_process_id",
                                    "The config process ID must be unique within a phase."
                                );
                            }
                        }
                    }

                    // ----------------------------
                    // Tasks must be unique within each process
                    // ----------------------------
                    foreach ($phase['processes'] ?? [] as $processIndex => $process) {

                        foreach (($process['tasks'] ?? []) as $taskIndex => $task) {
                            $taskId = $task['config_task_id'];

                            $taskMap[$taskId][] = $taskIndex;
                        }

                        foreach ($taskMap ?? [] as $taskId => $indexes) {
                            if (count($indexes) > 1) {
                                foreach ($indexes as $taskIndex) {
                                    $validator->errors()->add(
                                        "phases.$phaseIndex.processes.$processIndex.tasks.$taskIndex.config_task_id",
                                        "The config task ID must be unique within a process."
                                    );
                                }
                            }
                        }

                        unset($taskMap);
                    }
                }
            }
        ];
    }
}
