<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuotationRequest extends FormRequest
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
        return [
            'company.name' => 'required|string',
            'company.address' => 'required|string',
            'company.contact_person' => 'required|string',
            'company.contact_number' => 'required|string|min:11|max:11|regex:/^09\d{9}$/',
            'company.email' => 'required|email',
            'service.type' => ['required', 'string', Rule::in(['IMPORT', 'EXPORT', 'BUSINESS SOLUTION'])],
            'service.transport_mode' => ['required', 'string', Rule::in(['SEA', 'AIR'])],
            'service.options' => 'required|array',
            'commodity.commodity' => 'required|string',
            'commodity.cargo_type' => ['required', 'string', Rule::in(['CONTAINERIZED', 'LCL'])],
            // 'commodity.cargo_volume' => 'required_if:cargo_type,LCL|numeric|min:1',
            'commodity.container_size' => 'required_if:cargo_type,CONTAINERIZED|string',
            'shipment.origin' => 'required|string',
            'shipment.destination' => 'required|string',
            'files' => ['required', 'array'],
            'files.*' => ['required', 'file', 'mimes:pdf,png,jpg']
        ];
    }
}
