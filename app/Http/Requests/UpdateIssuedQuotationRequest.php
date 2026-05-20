<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\QuotationTemplate;
use Illuminate\Validation\Rule;

class UpdateIssuedQuotationRequest extends FormRequest
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

    $templateId = $this->input('template_id', $quotation->template_id);

    $template = QuotationTemplate::with([
        'detailConfigs.dropdownOptions',
        'templateCharges.allowedReceiptCharges',
        'quotationFields'
    ])->find($templateId);

    $detailsConfigCount = count($template->detailConfigs);

    return [
        'subject' => ['required', 'string', 'max:255'],
        'message' => ['required', 'string'],
        'rate_validity' => ['required', 'date', Rule::date()->afterToday()],

        'detail_values' => [
            'required', 'array', 'min:1', 'size:' . $detailsConfigCount
        ],

        'detail_values.*.label' => [
            'required',
            'string',
            'distinct',
            function ($attribute, $value, $fail) use ($template) {
                if (!$template) return;

                $exists = $template->detailConfigs()
                    ->where('label', $value)
                    ->exists();

                if (!$exists) {
                    $fail("'{$value}' is not a valid detail label for the selected template.");
                }
            }
        ],

        'detail_values.*.value' => [
            'required',
            'string',
            'max:255',
            function ($attribute, $value, $fail) use ($template) {
                if (!$template) return;

                $index = explode('.', $attribute)[1];
                $label = $this->input("detail_values.$index.label");

                $existingDetailConfig = $template->detailConfigs()
                    ->where('label', $label)
                    ->first();

                if (!$existingDetailConfig) return;

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
            'required', 'string', 'distinct'
        ],
        'charges.*.items.*.currency_label' => [
            'required', 
            'string', 
            Rule::exists('billing_configurations', 'label')
                ->where(fn ($query) => $query->where('type', 'CURRENCY'))
        ],
        'charges.*.items.*.uom_label' => [
            'required', 
            'string',
            Rule::exists('billing_configurations', 'label')
                ->where(fn ($query) => $query->where('type', 'UOM'))
        ],
        'charges.*.items.*.amount' => [
            'required', 'numeric', 'decimal:0,2','max:9999999999999.99'
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
        'signatory.signature_file' => [
            'nullable',
            'file',
            'mimes:png,jpg,jpeg'
        ],

        'issued_quotation_file' => ['required', 'file', 'mimes:pdf']
    ];
}
}
