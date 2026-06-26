<?php

namespace App\Http\Requests\PlanningTimeline;

use App\Models\PlanningTimeline\Timeline\TimelineTask;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AssignTimelineTaskRequest extends FormRequest
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
            'assignments'                   => ['required', 'array', 'min:1'],
            'assignments.*.task_id'         => ['required', 'integer', 'distinct'],
            'assignments.*.user_ids'        => ['required', 'array', 'min:1'],
            'assignments.*.user_ids.*'      => ['required', 'integer', 'exists:users,id'],
            'assignments.*.target_datetime' => ['required', 'date', 'after_or_equal:today']
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $timeline = $this->route('timeline');

                $validTaskIds = TimelineTask::whereHas('process.phase', function($query) use ($timeline) {
                        $query->where('planning_timeline_id', $timeline->id);
                    })->pluck('id')->all();

                foreach ($this->input('assignments') as $index => $assignment) {
                    if (!in_array($assignment['task_id'], $validTaskIds)) {
                        $validator->errors()->add(
                            "assignments.$index.task_id",
                            'The selected task id is invalid'
                        );
                    }
                }
            }
        ];
    }
}
