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
        // if ($this->input('services') === 'LOGISTICS') {
            return [
                // 'services' => 'required|in:LOGISTICS,REGULATORY',
                'company.name' => 'required|string',
                'company.address' => 'required|string',
                'company.contact_person' => 'required|string',
                'company.contact_number' => 'required|string|min:11|max:11|regex:/^09\d{9}$/',
                'company.email' => 'required|email',
                // 'service.type' => ['required', 'string', Rule::in(['IMPORT', 'EXPORT'])],
                'service.type' => ['required', 'string', Rule::in(['IMPORT', 'EXPORT', 'BUSINESS SOLUTION'])],
                'service.transport_mode' => ['required', 'string', Rule::in(['SEA', 'AIR'])],
                'service.options' => 'required|array',
                'commodity.commodity' => 'required|string',
                'commodity.cargo_type' => ['required', 'string', Rule::in(['CONTAINERIZED', 'LCL'])],
                'commodity.container_size' => ['required_if:commodity.cargo_type,CONTAINERIZED', function ($attribute, $value, $fail) {
                    if ($this->input('commodity.cargo_type') === 'CONTAINERIZED' && empty($value)) {
                        $fail('The container size is required when cargo type is CONTAINERIZED.');
                    }
                }],
                'shipment.origin' => 'required|string',
                'shipment.destination' => 'required|string',
                'documents' => ['required', 'array'],
                'documents.*' => ['required', 'file', 'mimes:pdf,png,jpg,doc,docx,heic,xls,xlsx'],
                'remarks' => ['nullable', 'string']
            ];
        // } elseif ($this->input('services') === 'REGULATORY') {
        //     return [
        //         'services' => 'required|in:LOGISTICS,REGULATORY',
        //         'company.contact_person' => 'required|string',
        //         'company.name' => 'required|string',
        //         'company.address' => 'required|string',
        //         'company.position' => 'required|string',
        //         'company.contact_number' => 'required|string|min:11|max:11|regex:/^09\d{9}$/',
        //         'company.email' => 'required|email',
        //         'company.business_type' => 'required',
        //         'type_of_regulatory_assistance' => 'required|array',
        //         'type_of_regulatory_assistance.*' => 'required|string',
        //         'service_level' => 'required|string|in:NEW,RENEWAL',
        //         'message' => 'nullable|string',
        //         'documents' => ['required', 'array'],
        //         'documents.*' => ['required', 'file', 'mimes:pdf,png,jpg,doc,docx,heic,xls,xlsx'],
        //         'remarks' => ['nullable', 'string']
        //     ];
        // }
    }
}
