<?php

namespace App\Http\Requests\PlanningTimeline;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTemplatePhaseHeadingRequest extends FormRequest
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
        $templatePhase = $this->route('phase'); 

        return [
            'headings'                     => ['required', 'array'],
            'headings.*.id'                => [
                'sometimes', 'integer', 'distinct',
                Rule::exists('planning_template_phase_headings', 'id')
                    ->where('template_phase_id', $templatePhase->id)
            ], 
            'headings.*.name'              => ['required', 'string', 'max:100', 'distinct'], 
            'headings.*.input_type'        => ['required', 'string', 'in:TEXT,NUMBER,DATETIME'], 
            'headings.*.sort_order'        => ['required', 'integer', 'distinct']
        ];
    }

    public function after() {
        return [
            function (Validator $validator) {
                $sortOrders = collect($this->input('headings'))
                    ->pluck('sort_order')
                    ->sort()
                    ->values()
                    ->all();

                $expectedOrder = range(1, count($sortOrders));

                if ($sortOrders !== $expectedOrder) {
                    $validator->errors()->add(
                        'headings.*.sort_order',
                        'The sort order values must be sequential starting from 1.'
                    );
                }

                $phase = $this->route('phase');

                $defaultHeadings = $phase->headings()
                    ->defaults()
                    ->get()
                    ->keyBy('id');

                foreach ($this->input('headings', []) as $index => $heading) {
                    if (!isset($heading['id'])) {
                        continue;
                    }

                    $defaultHeading = $defaultHeadings->get($heading['id']);

                    if (!$defaultHeading) {
                        continue;
                    }

                    if (
                        $heading['name'] !== $defaultHeading->name ||
                        $heading['input_type'] !== $defaultHeading->input_type
                    ) {
                        $validator->errors()->add(
                            "headings.{$index}.id",
                            'Default headings cannot have their name or input type modified.'
                        );
                    }
                }
            }          
        ];
    }
}
