<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestQuotationReassignmentRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'in:WORKLOAD,EMERGENCY / LEAVE,CLIENT REQUEST'],
            'additional_details' => ['sometimes', 'nullable', function ($attribute, $value, $fail) {
                if ($this->input('additional_details') === "" || empty($value)) {
                    $value = null; // Convert empty string to null
                    return;
                }
            }],
        ];
    }
}
