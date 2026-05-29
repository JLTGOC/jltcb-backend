<?php

namespace App\Http\Requests\Quotation;

use App\Models\QuotationTemplate\QuotationTemplate;
use Illuminate\Foundation\Http\FormRequest;

class QuotationClientInputsRequest extends FormRequest
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

        return [
            'template_id' => [
                'required',
                'integer',
                'exists:quotation_templates,id',
                function ($attribute, $value, $fail) use ($quotation) {
                    if (!$quotation) {
                        return;
                    }

                    if ($quotation->regulatoryService) {
                        $type = 'REGULATORY';
                    } elseif ($quotation->logisticsService) {
                        $type = 'LOGISTICS';
                    } else {
                        return;
                    }

                    $template = QuotationTemplate::find($value);

                    if (!$template) {
                        return;
                    }

                    $quotationField = $template->quotationFields()->first();

                    if (!$quotationField || $quotationField->quotation_type !== $type) {
                        $fail('The template id is not compatible with this quotation');
                    }
                },
            ],
        ];
    }
}
