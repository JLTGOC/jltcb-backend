<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Closure;
use App\Models\BillingConfiguration;
use Illuminate\Validation\Rule;

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
            'service_type' => ['sometimes', 'required', 'string', 'in:REGULATORY,LOGISTICS'],
            'is_active' => ['sometimes', 'boolean'],

            'detail_config_ids' => ['sometimes', 'required', 'array'],
            'detail_config_ids.*' => ['integer', 'exists:details_configurations,id'],

            'template_charges' => ['sometimes', 'required', 'array'],
            'template_charges.*.id' => ['sometimes', 'exists:template_charges,id', 'distinct'],
            'template_charges.*.name' => [
                'required_with:template_charges', 'string', 'max:255', 'distinct',  
                Rule::unique('template_charges', 'name')
                    ->where('template_id', $template->id)
                    ->ignore($template->id)
            ],
            'template_charges.*.receipt_option_ids' => ['sometimes', 'required', 'array'],
            'template_charges.*.receipt_option_ids.*' => [
                'sometimes',
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
