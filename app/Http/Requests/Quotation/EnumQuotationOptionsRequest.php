<?php

namespace App\Http\Requests\Quotation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\ServiceType;

class EnumQuotationOptionsRequest extends FormRequest
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
        return [
            'service' => 'sometimes|in:LOGISTICS,REGULATORY',
            'service_type' => ['sometimes', function ($attribute, $value, $fail) {
                $service = $this->input('service');

                if (!$service) {
                    $fail("The service field is required when {$attribute} is provided.");
                    return;
                }

                $allowedTypes = [];

                if ($service === 'LOGISTICS') {
                    $allowedTypes = ServiceType::where('service', 'LOGISTICS')->pluck('name')->toArray();
                } elseif ($service === 'REGULATORY') {
                    $allowedTypes = ServiceType::where('service', 'REGULATORY')->pluck('name')->toArray();
                }

                if (!in_array($value, $allowedTypes, true)) {
                    $fail("The {$attribute} must be one of: ".implode(', ', $allowedTypes).'.');
                }
            }],
            'client_id' => 'sometimes|nullable|exists:users,id',
            'client_search' => 'sometimes|nullable|string',
        ];
    }
}
