<?php

namespace App\Http\Requests\JobOrder;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RequestReassignmentRequest extends FormRequest
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
            'reason' => 'required|string|in:WORKLOAD,EMERGENCY / LEAVE,CLIENT REQUEST',
            'additional_details' => ['sometimes', 'nullable', function ($attribute, $value, $fail) {
                if ($this->additional_details === "" || empty($value)) {
                    $value = null;
                    return;
                }
            }],
        ];
    }
}
