<?php

namespace App\Http\Requests\JobOrder;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\{
    JobOrder,
    ServiceLevel,
    BillingMode
};
use Illuminate\Validation\Rule;

class StoreJobOrderRequest extends FormRequest
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
        $setNullRule = function ($field) {
            return function ($attribute, $value, $fail) use ($field) {
                if (is_null($value) || $value === '') {
                    return $value = null;
                }
            };
        };

        $rules = [
            'quotation_reference_number' => 'sometimes|string|unique:job_orders,reference_number',
            'job_type' => 'required|string|in:LOGISTICS,REGULATORY',
            'subject.subject' => 'sometimes|nullable|string',
            'subject.email_body' => 'sometimes|nullable|string',
            'client.client_type' => 'required|string|in:NEW,RENEWAL',
            'client.accredited' => 'required|string|in:REGULAR,EXPEDITED',
            'client.service_type' => 'required_if:job_type,REGULATORY|string',
            'client.tone_and_attitude' => 'sometimes|nullable|string',
            'client.remarks' => 'sometimes|nullable|string',
            'service.service_level' => ['required_if:job_type,LOGISTICS', 'string', Rule::in(ServiceLevel::pluck('name')->toArray())],
            'service.bl_no' => 'required_if:job_type,LOGISTICS|string',
            'service.eta' => 'required_if:job_type,LOGISTICS|date',
            'service.etd' => 'required_if:job_type,LOGISTICS|date|after_or_equal:service.eta',
            'shipment.hs_code' => ['sometimes','nullable','string', $setNullRule('shipment.hs_code')],
            'shipment.rod' => ['sometimes','nullable','string', $setNullRule('shipment.rod')],
            'shipment.permits' => ['sometimes','nullable','string', $setNullRule('shipment.permits')],
            'shipment.if_coordinated' => ['sometimes','nullable','string', $setNullRule('shipment.if_coordinated')],
            'shipment.special_remarks' => ['sometimes','nullable','string', $setNullRule('shipment.special_remarks')],
            'target.delivery_date' => ['sometimes','nullable','string', $setNullRule('target.delivery_date')],
            'target.completion_date' => ['sometimes','nullable','string', $setNullRule('target.completion_date')],
            'target.special_remarks' => ['sometimes','nullable','string', $setNullRule('target.special_remarks')],
            'billing.terms_of_payment' => ['sometimes','nullable','string', $setNullRule('billing.terms_of_payment')],
            'billing.billing_date' => 'sometimes|nullable|date',
            'billing.shall_be_billed' => ['sometimes', 'string', Rule::in(BillingMode::pluck('name')->toArray())],
            'billing.listed_docs' => ['sometimes','nullable','string', $setNullRule('billing.listed_docs')],
            'billing.attached_docs' => 'sometimes|nullable|array',
            'billing.attached_docs.*' => 'file|mimes:pdf,doc,docx,png,jpg,jpeg|max:2048',
        ];

        $platform = strtolower($this->header('Platform', 'mobile'));
        $isWeb = $platform === 'web';

        if ($isWeb) {
            $rules['subject.date'] = 'required|date';
        }

        return $rules;
    }
}
