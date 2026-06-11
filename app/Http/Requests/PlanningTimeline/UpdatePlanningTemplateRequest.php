<?php

namespace App\Http\Requests\PlanningTimeline;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePlanningTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function rules(): array
    {
        $planningTemplate = $this->route('template');

        return [
            'name' => ['required', 'string', 'max:255', 'unique:planning_templates,name,' . $planningTemplate->id],
            'config_version_number' => ['required', 'integer'],
            'template_version_number' => ['required', 'integer'],

            'phases'                   => ['required', 'array', 'min:1'],
            'phases.*.config_phase_id' => ['required', 'integer', 'distinct'],
            'phases.*.sort_order'      => ['required', 'integer', 'min:1', 'distinct'],

            'phases.*.processes'                     => ['required', 'array', 'min:1'],
            'phases.*.processes.*.config_process_id' => ['required', 'integer', 'distinct'],

            'phases.*.processes.*.tasks'                   => ['required', 'array', 'min:1'],
            'phases.*.processes.*.tasks.*.config_task_id'  => ['required', 'integer', 'distinct'],
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
            }
        ];
    }
}
