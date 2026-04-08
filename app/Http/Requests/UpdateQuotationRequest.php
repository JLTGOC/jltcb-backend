<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $quotation = $this->route('quotation');

        $baseRules = [
            'services' => 'sometimes|in:LOGISTICS,REGULATORY',
            'documents' => ['nullable', 'array'],
            'documents.*' => ['file', 'mimes:pdf,png,jpg,doc,docx,heic,xls,xlsx'],
            'removed_documents' => ['nullable', 'array'],
            'removed_documents.*' => [
                'integer',
                Rule::exists('quotation_files', 'id')->where(function ($query) use ($quotation) {
                    $query->where('quotation_id', $quotation->id);
                }),
            ],
        ];

        $serviceType = $this->input('services');

        if (!$serviceType && $quotation) {
            if ($quotation->logisticsService) {
                $serviceType = 'LOGISTICS';
            } elseif ($quotation->regulatoryService) {
                $serviceType = 'REGULATORY';
            }
        }

        if (!$serviceType) {
            if ($this->hasAny(['business_type', 'type_of_regulatory_assistance', 'service_level', 'message'])) {
                $serviceType = 'REGULATORY';
            } else {
                $serviceType = 'LOGISTICS';
            }
        }

        if ($serviceType === 'REGULATORY') {
            return array_merge($baseRules, [
                'company.contact_person' => 'sometimes|string',
                'company.name' => 'sometimes|string',
                'company.address' => 'sometimes|string',
                'company.position' => 'sometimes|string',
                'company.contact_number' => 'sometimes|string|min:11|max:11|regex:/^09\d{9}$/',
                'company.email' => 'sometimes|email',
                'company.business_type' => 'sometimes|string',
                'type_of_regulatory_assistance' => 'sometimes|array',
                'type_of_regulatory_assistance.*' => 'sometimes|string',
                'service_level' => 'sometimes|string|in:NEW,RENEWAL',
                'message' => 'nullable|string',
            ]);
        }

        return array_merge($baseRules, [
            'company.name' => 'sometimes|string',
            'company.address' => 'sometimes|string',
            'company.contact_person' => 'sometimes|string',
            'company.contact_number' => 'sometimes|string|min:11|max:11|regex:/^09\d{9}$/',
            'company.email' => 'sometimes|email',
            'service.type' => ['sometimes', 'string', Rule::in(['IMPORT', 'EXPORT', 'BUSINESS SOLUTION'])],
            'service.transport_mode' => ['sometimes', 'string', Rule::in(['SEA', 'AIR'])],
            'service.options' => 'sometimes|array',
            'commodity.commodity' => 'sometimes|string',
            'commodity.cargo_type' => ['sometimes', 'string', Rule::in(['CONTAINERIZED', 'LCL'])],
            'commodity.container_size' => 'required_if:commodity.cargo_type,CONTAINERIZED|string',
            'shipment.origin' => 'sometimes|string',
            'shipment.destination' => 'sometimes|string',
            'remarks' => ['nullable', 'string'],
        ]);
    }

    public function messages()
    {
        return [
            'removed_documents.*.exists' => 'The Quotation File ID does not belong to this quotation OR does not exist'
        ];
    }
}
