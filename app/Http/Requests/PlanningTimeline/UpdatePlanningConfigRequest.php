<?php

namespace App\Http\Requests\PlanningTimeline;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanningConfigRequest extends FormRequest
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
            'version_number' => 'required|integer',

            'phases'        => 'required|array',
            'phases.*.id'   => 'sometimes|integer',
            'phases.*.name' => 'required|string|max:255',

            'processes'         => 'required|array',
            'processes.*.id'    => 'sometimes|integer',
            'processes.*.name'  => 'required|string|max:255',

            'tasks'              => 'required|array',
            'tasks.*.id'         => 'sometimes|integer',
            'tasks.*.name'       => 'required|string|max:255',
        ];
    }
}
