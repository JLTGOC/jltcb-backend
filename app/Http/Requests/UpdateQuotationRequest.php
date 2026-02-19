<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\ServiceOption;

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

        return [
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
            // 'commodity.cargo_volume' => 'required_if:cargo_type,LCL|numeric|min:1',
            'commodity.container_size' => 'required_if:cargo_type,CONTAINERIZED|string',
            'shipment.origin' => 'sometimes|string',
            'shipment.destination' => 'sometimes|string',
            'documents' => ['nullable','array'],
            'documents.*' => ['file', 'mimes:pdf,png,jpg'],
            'removed_documents' => ['nullable', 'array'],
            'removed_documents.*' => [
                'integer',
                Rule::exists('quotation_files', 'id')->where(function($query) use ($quotation) {
                    $query->where('quotation_id', $quotation->id);
                })
            ]
        ];
    }

    public function messages()
    {
        return [
            'removed_documents.*.exists' => 'The Quotation File ID does not belong to this quotation OR does not exist'
        ];
    }
}
