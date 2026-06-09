<?php

namespace App\Http\Requests\Quotation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\{
    ServiceType,
    ServiceOption,
    ContainerSize,
    BusinessType,
    RegulatoryAssistanceType,
    User
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
            $rules = [
                'services' => 'required|in:LOGISTICS,REGULATORY',
                'company.name' => 'required|string',
                'company.address' => 'required|string',
                'company.contact_person' => 'required|string',
                'company.contact_number' => 'required|string|min:11|max:11|regex:/^09\d{9}$/',
                'company.email' => 'required|email',
                'service.type' => ['required', 'string', Rule::in(ServiceType::where('service', 'LOGISTICS')->pluck('name')->toArray())],
                'service.transport_mode' => ['required', 'string', Rule::in(['SEA', 'AIR'])],
                'service.options' => 'required|array',
                'service.options.*' => ['required', 'string', Rule::in(ServiceOption::where('service_type_id', ServiceType::where('name', $this->input('service.type'))->first()->id)->pluck('name')->toArray())],
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
                'documents' => ['sometimes', 'nullable', 'array'],
                'documents.*' => ['required_with:documents', 'file', 'mimes:pdf,png,jpg,doc,docx,heic,xls,xlsx'],
                'remarks' => ['nullable', 'string']
            ];
        } elseif ($this->input('services') === 'REGULATORY') {
            $rules = [
                'services' => 'required|in:LOGISTICS,REGULATORY',
                'full_name' => 'required|string',
                'company.contact_person' => ['sometimes', 'nullable', 'string', function ($attribute, $value, $fail) {
                    if ($this->input('company.contact_person') === "" || empty($value)) {
                        $value = null; // Convert empty string to null
                        return;
                    }
                }],
                'company.cp_contact_number' => ['sometimes', 'nullable', 'string', 'min:11', 'max:11', 'regex:/^09\d{9}$/', function ($attribute, $value, $fail) {
                    if ($this->input('company.cp_contact_number') === "" || empty($value)) {
                        $value = null; // Convert empty string to null
                        return;
                    }
                }],
                'company.name' => 'required|string',
                'company.address' => 'required|string',
                'company.position' => 'sometimes|nullable|string',
                'company.contact_number' => 'required|string|min:11|max:11|regex:/^09\d{9}$/',
                'company.email' => 'required|email',
                'company.business_type' => ['sometimes', 'nullable', Rule::in(BusinessType::pluck('name')->toArray())],
                'type_of_regulatory_assistance' => 'required|array',
                'type_of_regulatory_assistance.*' => ['required', 'string'],
                'service_level' => 'required|string|in:NEW,RENEWAL',
                'message' => ['nullable', 'string', function ($attribute, $value, $fail) {
                    if ($this->input('message') === "" || empty($value)) {
                        $value = null; // Convert empty string to null
                        return;
                    }
                }],
                'documents' => ['sometimes', 'nullable', 'array'],
                'documents.*' => ['required_with:documents', 'file', 'mimes:pdf,png,jpg,doc,docx,heic,xls,xlsx'],
            ];
        }

        if (auth()->user()->hasRole(['Account Specialist, Lead Account Specialist', 'Client Success'])) {
            $rules['client'] = 'required';
        }

        return $rules;
    }
}
