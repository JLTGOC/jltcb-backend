<?php

namespace App\Http\Requests;

use App\Models\QuotationTemplate;
use App\Rules\UniqueReceiptChargeLabelRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIssuedQuotationRequest extends FormRequest
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
        $template = QuotationTemplate::with([
            'detailConfigs.dropdownOptions', 
            'templateCharges.allowedReceiptCharges',
            'quotationFields'
        ])->find($this->template_id);

        $quotation = $this->route('quotation');

        $detailsConfigCount = count($template->detailConfigs);

        return [
            'template_id' => [
                'required', 
                'integer', 
                'exists:quotation_templates,id',
                function ($attribute, $value, $fail) use ($quotation, $template) {
                    // only allow template compatible to quotation type
                    // template type of Regulatory Service should not be allowed to be used by quotation with Logistics service
                    if ($quotation->regulatoryService) {
                        $type = 'REGULATORY';
                    } elseif ($quotation->logisticsService) {
                        $type = 'LOGISTICS';
                    } 

                    $quotationFieldType = $template->quotationFields()->first()->quotation_type;

                    if ($type !== $quotationFieldType) {
                        $fail('The template id is not compatible with this quotation');
                    }
                }
            ],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'rate_validity' => ['required', 'date', Rule::date()->afterToday()],

            'uom' => ['required', 'string', Rule::exists('billing_configurations', 'label')
                    ->where(fn ($query) => $query->where('type', 'UOM'))],
            'currency' => ['required', 'string', Rule::exists('billing_configurations', 'label')
                    ->where(fn ($query) => $query->where('type', 'CURRENCY'))],

            'detail_values' => [
                'required', 'array', 'min:1', 'size:' . $detailsConfigCount
            ],
            'detail_values.*.label' => [
                'required', 'string', 'distinct', 
                function ($attribute, $value, $fail) use ($template) {

                    $exists = $template->detailConfigs()->where('label', $value)->exists();
                    
                    if (!$exists) {
                        $fail("'{$value}' is not a valid detail label for the selected template.");
                    }
                }
            ],
            'detail_values.*.value' => [
                'required', 'string', 'max:255',
                function ($attribute, $value, $fail) use ($template) {

                    $index = explode('.', $attribute)[1];
                    $label = $this->input("detail_values.$index.label");

                    $existingDetailConfig = $template->detailConfigs()
                        ->where('label', $label)
                        ->first();

                    if (!$existingDetailConfig) {
                        return; 
                    }

                    if ($existingDetailConfig->type === 'DROPDOWN') {
                        $exists = $existingDetailConfig->dropdownOptions()
                            ->where('name', $value)
                            ->exists();

                        if (!$exists) {
                            $fail("'{$value}' is not a valid option for {$label}.");
                        }
                    }
                }
            ],

            "charges" => ['required', 'array', 'min:1'],
            'charges.*.name' => [
                'required', 'string', 'distinct',
                function ($attribute, $value, $fail) use ($template) {

                    $exists = $template->templateCharges()->where('name', $value)->exists();
                    
                    if (!$exists) {
                        $fail("'{$value}' is not a valid Charge Name for the selected template.");
                    }
                }
            ],
            'charges.*.items' => ['required', 'array', 'min:1'],
            'charges.*.items.*.receipt_charge_label' => [
                'required', 'string',
            ],
            'charges.*.items.*.amount' => [
                'required', 'numeric', 'decimal:0,2','max:9999999999999.99'
            ], 
            'charges.*.items.*.quantity' => [
                Rule::requiredIf($this->uom === 'PER CONTAINER'),
                'nullable',
                'integer',
                'min:1',
            ],
            'charges.*.items.*.container_size' => [
                Rule::requiredIf($this->uom === 'PER CONTAINER'),
                'nullable',
                'string',
            ],

            'standard_config.name' => ['required', 'string', 'max:255'],
            'standard_config.policies' => ['required', 'string'],
            'standard_config.terms_and_conditions' => ['required', 'string'],
            'standard_config.banking_details' => ['required', 'string'],
            'standard_config.footer' => ['required', 'string', 'max:255'],

            'signatory.closing_statement' => ['required', 'string', 'max:255'],
            'signatory.is_authorized_signatory' => ['required', 'boolean'],
            'signatory.authorized_signatory_name' => ['required', 'string', 'max:255'],
            'signatory.position' => ['required', 'string', 'max:255'],
            'signatory.signature_file' => ['required', 'file', 'mimes:png,jpg,jpeg'],

            'issued_quotation_file' => ['required', 'file', 'mimes:pdf']
        ]; 
    }

    public function after(): array
{
    return [
        new UniqueReceiptChargeLabelRule(
            uom: $this->input('uom'),
            charges: $this->input('charges', [])
        ),
    ];
}
}
