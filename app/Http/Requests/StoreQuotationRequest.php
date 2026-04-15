<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\{
    ServiceOption,
    ContainerSize,
    BusinessType,
    RegulatoryAssistanceType
};

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
        if ($this->input('services') === 'LOGISTICS') {
            return [
                'services' => 'required|in:LOGISTICS,REGULATORY',
                'company.name' => 'required|string',
                'company.address' => 'required|string',
                'company.contact_person' => 'required|string',
                'company.contact_number' => 'required|string|min:11|max:11|regex:/^09\d{9}$/',
                'company.email' => 'required|email',
                'service.type' => ['required', 'string', Rule::in(['IMPORT', 'EXPORT'])],
                'service.transport_mode' => ['required', 'string', Rule::in(['SEA', 'AIR'])],
                'service.options' => 'required|array',
                'service.options.*' => ['required', 'string', Rule::in(ServiceOption::pluck('name')->toArray())],
                'commodity.commodity' => 'required|string',
                'commodity.cargo_type' => ['required', 'string', Rule::in(['CONTAINERIZED', 'LCL'])],
                'commodity.container_size' => ['required_if:commodity.cargo_type,CONTAINERIZED', function ($attribute, $value, $fail) {
                    if ($this->input('commodity.cargo_type') === 'CONTAINERIZED' && empty($value)) {
                        $fail('The container size is required when cargo type is CONTAINERIZED.');
                    }
                    if ($this->input('commodity.cargo_type') === 'CONTAINERIZED' && !in_array($value, ContainerSize::pluck('size')->toArray())) {
                        $fail('The selected container size is invalid.');
                    }
                }],
                'shipment.origin' => 'required|string',
                'shipment.destination' => 'required|string',
                'documents' => ['required', 'array'],
                'documents.*' => ['required', 'file', 'mimes:pdf,png,jpg,doc,docx,heic,xls,xlsx'],
                'remarks' => ['nullable', 'string']
            ];
        } elseif ($this->input('services') === 'REGULATORY') {
            return [
                'services' => 'required|in:LOGISTICS,REGULATORY',
                'full_name' => 'required|string',
                'company.contact_person' => 'sometimes|string',
                'company.cp_contact_number' => 'sometimes|string|min:11|max:11|regex:/^09\d{9}$/',
                'company.name' => 'required|string',
                'company.address' => 'required|string',
                'company.position' => 'required|string',
                'company.contact_number' => 'sometimes|string|min:11|max:11|regex:/^09\d{9}$/',
                'company.email' => 'required|email',
                'company.business_type' => ['required', Rule::in(BusinessType::pluck('name')->toArray())],
                'type_of_regulatory_assistance' => 'required|array',
                'type_of_regulatory_assistance.*' => ['required', 'string', Rule::in(RegulatoryAssistanceType::pluck('name')->toArray())],
                'service_level' => 'required|string|in:NEW,RENEWAL',
                'message' => 'nullable|string',
                'documents' => ['required', 'array'],
                'documents.*' => ['required', 'file', 'mimes:pdf,png,jpg,doc,docx,heic,xls,xlsx'],
                'remarks' => ['nullable', 'string']
            ];
        }
    }
}
