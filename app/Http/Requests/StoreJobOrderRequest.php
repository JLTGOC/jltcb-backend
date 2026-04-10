<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\JobOrder;

class StoreJobOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', JobOrder::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quotation_reference_number' => 'sometimes|string|unique:job_orders,reference_number',
            'job_type' => 'required|string|in:LOGISTICS,REGULATORY',
            'subject.subject' => 'required|string',
            'subject.email_body' => 'required|string',
            'client.client_type' => 'required|string|in:NEW,RENEWAL',
            'client.accredited' => 'required|string|in:REGULAR,EXPEDITED',
            'client.remarks' => 'nullable|string',
            'service.service_level' => 'required_if:job_type,LOGISTICS|string',
            'service.bl_no' => 'required_if:job_type,LOGISTICS|string',
            'service.eta' => 'required_if:job_type,LOGISTICS|date',
            'service.etd' => 'required_if:job_type,LOGISTICS|date|after_or_equal:service.eta',
            'shipment.hs_code' => 'nullable|string',
            'shipment.rod' => 'nullable|string',
            'shipment.permits' => 'nullable|string',
            'shipment.special_remarks' => 'nullable|string',
            'target.delivery_date' => 'nullable|string',
            'target.completion_date' => 'nullable|string',
            'target.special_remarks' => 'nullable|string',
            'billing.terms_of_payment' => 'nullable|string',
            'billing.billing_date' => 'nullable|date',
            'billing.shall_be_billed' => 'nullable|string',
        ];
    }
}
