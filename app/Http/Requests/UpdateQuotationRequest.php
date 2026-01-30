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
        return [
            'companyName' => 'sometimes|string',
            'companyAddress' => 'sometimes|string',
            'contactPerson' => 'sometimes|string',
            'contactNumber' => 'sometimes|string|min:11|max:11|regex:/^09\d{9}$/',
            'email' => 'sometimes|email',
            'serviceType' => ['sometimes', 'string', Rule::in(['IMPORT', 'EXPORT', 'BUSINESS SOLUTION'])],
            'transportMode' => ['sometimes', 'string', Rule::in(['SEA', 'AIR'])],
            'serviceOptions' => 'sometimes|array',
            'commodity' => 'sometimes|string',
            'cargoVolume' => ['sometimes', 'string', Rule::in(['CONTAINERIZED', 'LCL'])],
            'containerSize' => 'sometimes|string',
            'origin' => 'sometimes|string',
            'destination' => 'sometimes|string',
        ];
    }
}
