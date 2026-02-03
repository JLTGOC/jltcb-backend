<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuotationRequest extends FormRequest
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
            'company_name' => 'required|string',
            'company_address' => 'required|string',
            'contact_person' => 'required|string',
            'contact_number' => 'required|string|min:11|max:11|regex:/^09\d{9}$/',
            'email' => 'required|email',
            'service_type' => ['required', 'string', Rule::in(['IMPORT', 'EXPORT', 'BUSINESS SOLUTION'])],
            'transport_mode' => ['required', 'string', Rule::in(['SEA', 'AIR'])],
            'service_options' => 'required|array',
            'commodity' => 'required|string',
            'cargo_type' => ['required', 'string', Rule::in(['CONTAINERIZED', 'LCL'])],
            'origin' => 'required|string',
            'destination' => 'required|string',
        ];
    }
}
