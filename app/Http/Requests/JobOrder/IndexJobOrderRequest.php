<?php

namespace App\Http\Requests\JobOrder;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexJobOrderRequest extends FormRequest
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
        if (auth()->user()->hasRole(['Account Specialist', 'Lead Account Specialist'])) {
            $assignmentStatusRule = 'sometimes|string|in:PENDING,ACCEPTED,ALL';
        } else {
            $assignmentStatusRule = 'sometimes|string|in:AVAILABLE,ASSIGNED,REASSIGNMENT REQUESTED,ALL';
        }

        return [
            'filter.service' => 'sometimes|string|in:LOGISTICS,REGULATORY,ALL',
            'filter.assignment_status' => $assignmentStatusRule,
            'filter.service_type' => 'sometimes|string|in:IMPORT,EXPORT',
            'filter.completion_status' => 'sometimes|string|in:COMPLETED,IN PROGRESS',
            'search' => 'sometimes|string',
            'ops_search' => 'sometimes|string',
            'client_type' => 'sometimes|string|in:OLD,NEW',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'my_per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
            'my_page' => 'sometimes|integer|min:1',
        ];
    }
}
