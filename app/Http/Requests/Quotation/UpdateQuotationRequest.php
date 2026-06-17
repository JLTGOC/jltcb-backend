<?php

namespace App\Http\Requests\Quotation;

use App\Models\ServiceType;
use App\Models\ContainerSize;
use App\Models\QuotationFileChecklistItem;
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
        $serviceTypeId = ServiceType::where('name', $this->input('service.type'))->first()?->id ?? ($quotation->serviceType ? $quotation->serviceType->id : null);

        $baseRules = [
            'services' => 'sometimes|in:LOGISTICS,REGULATORY',
            'full_name' => 'sometimes||string',
            'company.name' => 'sometimes|string',
            'company.address' => 'sometimes|string',
            'company.contact_person' => ['sometimes', 'nullable', 'string', function ($attribute, $value, $fail) {
                if ($this->input('company.contact_person') === "" || empty($value)) {
                    $value = null; // Convert empty string to null
                    return;
                }
            }],
            'company.contact_number' => 'sometimes|string|min:11|max:11|regex:/^09\d{9}$/',
            'company.email' => 'sometimes|email',
            'service.type' => ['sometimes', 'string', Rule::in(ServiceType::where('service', $serviceDomain)->pluck('name')->toArray())],
            'service.options' => 'sometimes|array',
            'service.options.*' => ['sometimes', 'string', Rule::in(ServiceOption::where('service_type_id', $serviceTypeId)->orWhereNull('service_type_id')->pluck('name')->toArray())], 
            'commodity.commodity' => 'sometimes|nullable|string',
            'documents' => ['nullable', 'array'],
            'documents.*.file' => ['file', 'mimes:pdf,png,jpg,doc,docx,heic,xls,xlsx'],
            'documents.*.type' => ['string', Rule::in(QuotationFileChecklistItem::whereIn('visibility', [$this->input('services'), 'BOTH'])->pluck('name')->toArray())],
            'removed_documents' => ['nullable', 'array'],
            'removed_documents.*' => [
                'integer',
                Rule::exists('quotation_files', 'id')->where(function ($query) use ($quotation) {
                    $query->where('quotation_id', $quotation->id);
                }),
            ],
        ];

        $serviceDomain = $this->input('services');

        if (!$serviceDomain && $quotation) {
            if ($quotation->logisticsService) {
                $serviceDomain = 'LOGISTICS';
            } elseif ($quotation->regulatoryService) {
                $serviceDomain = 'REGULATORY';
            }
        }

        if (!$serviceDomain) {
            if ($this->hasAny(['business_type', 'type_of_regulatory_assistance', 'service_level', 'message'])) {
                $serviceDomain = 'REGULATORY';
            } else {
                $serviceDomain = 'LOGISTICS';
            }
        }

        if ($serviceDomain === 'REGULATORY') {
            return array_merge($baseRules, [
                'company.cp_contact_number' => ['sometimes', 'nullable', 'string', 'min:11', 'max:11', 'regex:/^09\d{9}$/', function ($attribute, $value, $fail) {
                    if ($this->input('company.cp_contact_number') === "" || empty($value)) {
                        $value = null; // Convert empty string to null
                        return;
                    }
                }],
                'company.position' => 'sometimes|nullable|string',
                'company.business_type' => 'sometimes|nullable|string',
                'type_of_regulatory_assistance' => 'sometimes|array',
                'type_of_regulatory_assistance.*' => 'sometimes|string',
                'service_level' => 'sometimes|string|in:NEW,RENEWAL',
                'message' => ['nullable', 'string', function ($attribute, $value, $fail) {
                    if ($this->input('message') === "" || empty($value)) {
                        $value = null; // Convert empty string to null
                        return;
                    }
                }],
            ]);
        } elseif ($serviceDomain === 'LOGISTICS') {
            return array_merge($baseRules, [
                'service.transport_mode' => ['sometimes', 'string', Rule::in(['SEA', 'AIR'])],
                'commodity.cargo_type' => ['sometimes', 'string', Rule::in(['CONTAINERIZED', 'LCL'])],
                'commodity.container_size' => 'required_if:commodity.cargo_type,CONTAINERIZED|string|in:' . implode(',', ContainerSize::pluck('size')->toArray()),
                'shipment.origin' => 'sometimes|string',
                'shipment.destination' => 'sometimes|string',
                'remarks' => ['nullable', 'string', function ($attribute, $value, $fail) {
                    if ($this->input('remarks') === "" || empty($value)) {
                        $value = null; // Convert empty string to null
                        return;
                    }
                }],
            ]);
        }
    }

    public function messages()
    {
        return [
            'removed_documents.*.exists' => 'The Quotation File ID does not belong to this quotation OR does not exist'
        ];
    }
}
