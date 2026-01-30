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
            'companyName' => 'required|string',
            'companyAddress' => 'required|string',
            'contactPerson' => 'required|string',
            'contactNumber' => 'required|string|min:11|max:11|regex:/^09\d{9}$/',
            'email' => 'required|email',
            'serviceType' => 'required|string',
            'transportMode' => 'required|string',
            'serviceOptions' => 'required|array',
            'commodity' => 'required|string',
            'cargoVolume' => 'required|string',
            'containerSize' => 'sometimes', Rule::requiredIf(strtoupper('cargoVolume'),'CONTAINERIZED'),
            'origin' => 'required|string',
            'destination' => 'required|string',
        ];
    }
}
