<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

class UpdateCompanyRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $basicInfoRules = [
            'basic_info.name' => 'sometimes|nullable|string|max:255',
            'basic_info.consignee_used' => 'sometimes|nullable|string|max:255',
            'basic_info.trade_name' => 'sometimes|nullable|string|max:255',
            'basic_info.account_handler_id' => ['sometimes',  'nullable', function ($attribute, $value, $fail) {
                if (!User::where('id', $value)->role(['Account Specialist', 'Client Success', 'Lead Account Specialist', 'Lead Client Success'])->exists()) {
                    $fail('The selected account handler is invalid.');
                }
            }],
            'basic_info.transaction_type_id' => 'sometimes|nullable',
            'basic_info.client_classification_id' => 'sometimes|nullable',
            'basic_info.company_type_id' => 'sometimes|nullable',
            'basic_info.business_type_id' => 'sometimes|nullable',
            'basic_info.business_registration_number' => 'sometimes|nullable|string|max:255',
            'basic_info.website' => 'sometimes|nullable|string|max:255',
            'basic_info.years_in_operation' => 'sometimes|nullable|integer|min:0',
            'basic_info.activation_date' => 'sometimes|nullable|date',
            'basic_info.industry' => 'sometimes|nullable|array',
            'basic_info.industry.*' => 'sometimes',
        ];

        $addressRules = [
            'address.registered_address' => 'sometimes|nullable|string|max:255',
            'address.office_address' => 'sometimes|nullable|string|max:255',
            'address.usual_port' => 'sometimes|nullable|string|max:255',
            'address.origin_country' => 'sometimes|nullable|string|max:255',
            'address.destination_country' => 'sometimes|nullable|string|max:255',
            'address.warehouse_addresses' => 'sometimes|nullable|array',
            'address.warehouse_addresses.*' => 'sometimes|string|max:255',
            'address.delivery_addresses' => 'sometimes|nullable|array',
            'address.delivery_addresses.*' => 'sometimes|string|max:255',
        ];

        $contactRules = [
            'primary.full_name' => 'sometimes|nullable|string|max:255',
            'primary.position' => 'sometimes|nullable|string|max:255',
            'primary.email' => 'sometimes|nullable|email|max:255',
            'primary.contact_number' => 'sometimes|nullable|string|max:20',
            'secondary.full_name' => 'sometimes|nullable|string|max:255',
            'secondary.position' => 'sometimes|nullable|string|max:255',
            'secondary.email' => 'sometimes|nullable|email|max:255',
            'secondary.contact_number' => 'sometimes|nullable|string|max:20',
            'billing.full_name' => 'sometimes|nullable|string|max:255',
            'billing.position' => 'sometimes|nullable|string|max:255',
            'billing.email' => 'sometimes|nullable|email|max:255',
            'billing.contact_number' => 'sometimes|nullable|string|max:20',
        ];

        $registrationRules = [
            'registration.tin' => 'sometimes|nullable|string|max:255',
            'registration.bir_registration_number' => 'sometimes|nullable|string|max:255',
            'registration.cprs_status' => 'sometimes|nullable|in:ACTIVE,INACTIVE',
            'registration.importer_accreditation_number' => 'sometimes|nullable|string|max:255',
            'registration.exporter_accreditation_number' => 'sometimes|nullable|string|max:255',
            'registration.importer_accreditation_expiry' => 'sometimes|nullable|date',
            'registration.exporter_accreditation_expiry' => 'sometimes|nullable|date',
            'registration.special_permits' => 'sometimes|nullable|string|max:255',
            'registration.compliance_risk' => 'sometimes|nullable|string|max:255',
            'registration.representatives' => 'sometimes|nullable|array',
            'registration.representatives.*' => 'sometimes|string|max:255', 
        ];

        $pricingRules = [
            'pricing.service_rate' => 'sometimes|nullable|numeric|min:0',
            'pricing.special_discounts' => 'sometimes|nullable|numeric|min:0',
            'pricing.3pl_profit_range' => 'sometimes|nullable|numeric|min:0',
            'pricing.notes' => 'sometimes|nullable|string|max:255',
        ];

        $operationRules = [
            'operation.preferred_communication_style' => 'sometimes|nullable|string|max:255',
            'operation.response_time_expectation' => 'sometimes|nullable|string|max:255',
            'operation.client_specific_sop' => 'sometimes|nullable|string|max:255',
            'operation.approval_workflow' => 'sometimes|nullable|string|max:255',
            'operation.pre_alert_details' => 'sometimes|nullable|string|max:255',
            'operation.special_instructions' => 'sometimes|nullable|string|max:255',
        ];

        $monitoringRules = [
            'monitoring.past_issues' => 'sometimes|nullable|string|max:255',
            'monitoring.penalties' => 'sometimes|nullable|string|max:255',
            'monitoring.custom_flags' => 'sometimes|nullable|string|max:255',
            'monitoring.payment_delays' => 'sometimes|nullable|string|max:255',
            'monitoring.claims' => 'sometimes|nullable|string|max:255',
            'monitoring.notes' => 'sometimes|nullable|string|max:255',
        ];

        $documentRules = [
            'documents' => 'sometimes|nullable|array',
            'documents.*.name' => 'required_with:documents|string|max:255',
            'documents.*.file' => 'required_with:documents|file|max:10240',
            'documents_to_delete' => 'sometimes|nullable|array',
            'documents_to_delete.*' => 'sometimes|nullable|integer|exists:company_documents,id',
        ];

        $insightRules = [
            'insights.growth' => 'sometimes|nullable|in:LOW,MEDIUM,HIGH',
            'insights.expansion_plan' => 'sometimes|nullable|string|max:255',
            'insights.competitors' => 'sometimes|nullable|string|max:255',
            'insights.opportunities' => 'sometimes|nullable|string|max:255',
            'insights.notes' => 'sometimes|nullable|string|max:255',
        ];

        return array_merge($basicInfoRules, $addressRules, $contactRules, $registrationRules, $pricingRules, $operationRules, $monitoringRules, $documentRules, $insightRules);
    }
}
