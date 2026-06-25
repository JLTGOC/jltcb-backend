<?php

namespace App\Http\Requests\PlanningTimeline;

use App\Enums\DefaultPhaseHeading;
use App\Models\ServiceType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePlanningTimelineRequest extends FormRequest
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
            'planning_template_id'  => ['sometimes', 'nullable', 'integer', 'exists:planning_templates,id'],

            'save_as_template'                  => ['sometimes', 'required', 'array:name,service_type_id'],
            'save_as_template.name'             => ['required_with:save_as_template', 'string', 'max:100'],
            'save_as_template.service_type_id'  => [
                'required_with:save_as_template',
                'integer',
                Rule::exists('service_types', 'id'),
            ],

            'phases'                   => ['required', 'array', 'min:1'],
            'phases.*.name'            => ['required', 'string', 'max:100'],
            'phases.*.config_phase_id' => ['required_with:save_as_template', 'integer', 'distinct'],
            'phases.*.sort_order'      => ['required', 'integer', 'min:1', 'distinct'],

            'phases.*.processes'                     => ['required', 'array', 'min:1'],
            'phases.*.processes.*.name'              => ['required', 'string', 'max:100'],
            'phases.*.processes.*.config_process_id' => ['required_with:save_as_template', 'integer'],

            'phases.*.headings'              => ['required', 'array', 'min:1'],
            'phases.*.headings.*.key'        => ['nullable', 'string'],
            'phases.*.headings.*.name'       => ['required', 'string', 'max:255'],
            'phases.*.headings.*.input_type' => ['required', Rule::in(['TEXT', 'NUMBER', 'DATETIME'])],
            'phases.*.headings.*.sort_order' => ['required', 'integer', 'min:1'],

            'phases.*.processes.*.tasks'                   => ['required', 'array', 'min:1'],
            'phases.*.processes.*.tasks.*.name'            => ['required', 'string', 'max:100'],
            'phases.*.processes.*.tasks.*.config_task_id'  => ['required_with:save_as_template', 'integer'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {

                // ----------------------------
                // Phase sort_order must be sequential
                // ----------------------------
                $sortOrders = collect($this->input('phases', []))
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

                if (! $this->input('save_as_template')) {
                    return;
                }

                $requiredHeadingKeys = collect(DefaultPhaseHeading::cases())
                    ->pluck('value')
                    ->all();

                foreach ($this->input('phases', []) as $phaseIndex => $phase) {

                    // ----------------------------
                    // Headings must include default keys per phase
                    // ----------------------------
                    $keyOccurrences = [];

                    foreach (($phase['headings'] ?? []) as $headingIndex => $heading) {

                        $key = $heading['key'] ?? null;

                        if (! $key) {
                            continue;
                        }

                        $keyOccurrences[$key][] = $headingIndex;
                    }

                    // Every phase must contain all required keys
                    foreach ($requiredHeadingKeys as $requiredKey) {

                        if (! isset($keyOccurrences[$requiredKey])) {
                            $validator->errors()->add(
                                "phases.$phaseIndex.headings",
                                "The heading key '{$requiredKey}' is required for this phase."
                            );
                        }
                    }

                    // Required keys may only appear once within the phase
                    foreach ($requiredHeadingKeys as $requiredKey) {

                        if (
                            isset($keyOccurrences[$requiredKey]) &&
                            count($keyOccurrences[$requiredKey]) > 1
                        ) {
                            foreach ($keyOccurrences[$requiredKey] as $headingIndex) {
                                $validator->errors()->add(
                                    "phases.$phaseIndex.headings.$headingIndex.key",
                                    "The heading key '{$requiredKey}' may only appear once within a phase."
                                );
                            }
                        }
                    }

                    // =========================================================
                    // Phase Headings sort_order must be sequential
                    // =========================================================
                    $headingSortOrders = collect($phase['headings'] ?? [])
                        ->pluck('sort_order')
                        ->sort()
                        ->values()
                        ->all();

                    if (! empty($headingSortOrders)) {
                        $expectedHeadingOrder = range(1, count($headingSortOrders));

                        if ($headingSortOrders !== $expectedHeadingOrder) {
                            $validator->errors()->add(
                                "phases.$phaseIndex.headings.*.sort_order",
                                'The heading sort_order values must be sequential starting from 1 within each phase.'
                            );
                        }
                    }

                    // ----------------------------
                    // Processes must be unique within phase
                    // ----------------------------
                    $processGroups = collect($phase['processes'] ?? [])
                        ->groupBy('config_process_id');

                    foreach ($processGroups as $processId => $group) {

                        if ($group->count() <= 1) {
                            continue;
                        }

                        foreach ($group as $item) {

                            $processIndex = array_search(
                                $item,
                                $phase['processes'],
                                true
                            );

                            $validator->errors()->add(
                                "phases.$phaseIndex.processes.$processIndex.config_process_id",
                                'The config process ID must be unique within a phase.'
                            );
                        }
                    }

                    // ----------------------------
                    // Tasks must be unique within each process
                    // ----------------------------
                    foreach (($phase['processes'] ?? []) as $processIndex => $process) {

                        $taskMap = [];

                        foreach (($process['tasks'] ?? []) as $taskIndex => $task) {

                            $taskId = $task['config_task_id'] ?? null;

                            if ($taskId === null) {
                                continue;
                            }

                            $taskMap[$taskId][] = $taskIndex;
                        }

                        foreach ($taskMap as $taskId => $indexes) {

                            if (count($indexes) <= 1) {
                                continue;
                            }

                            foreach ($indexes as $taskIndex) {
                                $validator->errors()->add(
                                    "phases.$phaseIndex.processes.$processIndex.tasks.$taskIndex.config_task_id",
                                    'The config task ID must be unique within a process.'
                                );
                            }
                        }
                    }
                }
            }
        ];
    }
}
