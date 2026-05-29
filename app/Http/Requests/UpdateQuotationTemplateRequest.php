<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Closure;
use App\Models\QuotationTemplateConfig\BillingConfiguration;
use App\Models\QuotationField;

class UpdateQuotationTemplateRequest extends FormRequest
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
        $template = $this->route('template');

        return [
            'name' => [
                'required', 'string', 'max:255', 'unique:quotation_templates,name,' . $template->id
            ],
            'service_type' => ['sometimes', 'string', 'in:REGULATORY,LOGISTICS'],
            'is_active' => ['sometimes', 'boolean'],

            'detail_config_ids' => ['required', 'array'],
            'detail_config_ids.*' => ['integer', 'exists:details_configurations,id'],

            'quotation_field_ids' => ['required', 'array'],
            'quotation_field_ids.*' =>  
                function (string $attribute, mixed $value, Closure $fail) use ($template) {
                    $service_type = $template->service_type;

                    $exists = match($service_type) {
                        'REGULATORY' => QuotationField::regulatoryFields()
                            ->where('id', $value)->exists(),
                        'LOGISTICS' => QuotationField::logisticsFields()
                            ->where('id', $value)->exists()
                    };

                    if (!$exists) {
                        $fail('This id does not exist or does not belong to quotation fields with ' . $service_type . ' service type');
                    }
                },

            'template_charges' => ['required', 'array'],
            'template_charges.*.id' => ['sometimes', 'exists:template_charges,id', 'distinct'],
            'template_charges.*.name' => [
                'required_with:template_charges', 'string', 'max:255', 'distinct',
            ],
            'template_charges.*.receipt_option_ids' => ['required', 'array'],
            'template_charges.*.receipt_option_ids.*' => [
                'required', 
                'integer', 
                function (string $attribute, mixed $value, Closure $fail) {
                    $exists = BillingConfiguration::where('type', 'RECEIPT CHARGES')->where('id', $value)->exists();

                    if (!$exists) {
                        $fail("The id does not exist OR does not belong to billing configuration id's with RECEIPT CHARGES type");
                    }
                }
            ]
        ];
    }
}
