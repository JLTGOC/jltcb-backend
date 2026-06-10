<?php

namespace App\Http\Requests\Quotation;

use Illuminate\Foundation\Http\FormRequest;

class ReassignQuotationSpecialistRequest extends FormRequest
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
        $rules = [
            'status' => ['sometimes', 'nullable', 'in:APPROVED,REJECTED'],
            'as_id' => ['nullable', 'integer', 'exists:users,id'],
        ];

        if (!auth()->user()->hasRole('Lead Account Specialist')) {
            $rules['status'][] = function ($attribute, $value, $fail) {
                if (($value === null || is_empty($value) || $value === '')) {
                    $fail('The status field is required when the user is not a Lead Account Specialist.');
                }
            };
            $rules['as_id'][] = 'required_if:status,APPROVED';
        } else {
            $rules['as_id'][] = 'sometimes';
        }

        return $rules;
    }
}
