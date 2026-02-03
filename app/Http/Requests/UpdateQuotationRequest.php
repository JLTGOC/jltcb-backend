<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\ServiceOption;

class UpdateQuotationRequest extends FormRequest
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
            'company_name' => 'sometimes|string',
            'company_address' => 'sometimes|string',
            'contact_person' => 'sometimes|string',
            'contact_number' => 'sometimes|string|min:11|max:11|regex:/^09\d{9}$/',
            'email' => 'sometimes|email',
            'service_type' => ['sometimes', 'string', Rule::in(['IMPORT', 'EXPORT', 'BUSINESS SOLUTION'])],
            'transport_mode' => ['sometimes', 'string', Rule::in(['SEA', 'AIR'])],
            'service_options' => 'sometimes|array',
            'commodity' => 'sometimes|string',
            'cargo_type' => ['sometimes', 'string', Rule::in(['CONTAINERIZED', 'LCL'])],
            'origin' => 'sometimes|string',
            'destination' => 'sometimes|string',
        ];
    }
}
