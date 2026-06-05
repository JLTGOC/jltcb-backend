<?php

namespace App\Http\Requests\Quotation;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class IndexQuotationRequest extends FormRequest
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
            'filter.status' => 'required|in:REQUESTED,RESPONDED,ACCEPTED,DISCARDED',
            'filter.created_at' => 'sometimes|date_format:Y-m-d',
            'filter.assignment_status' => ['sometimes', function ($attribute, $value, $fail) {
                $user = auth()->user();
                $allowedStatuses = [];

                if ($user?->hasRole(['Lead Account Specialist', 'Client Success'])) {
                    $allowedStatuses = ['AVAILABLE', 'ASSIGNED', 'REASSIGNMENT REQUESTED', 'ALL'];
                } elseif ($user?->hasRole('Account Specialist')) {
                    $allowedStatuses = ['AVAILABLE', 'REASSIGNMENT REQUESTED', 'ALL'];
                } else {
                    $fail("The {$attribute} filter is not applicable for your role.");
                    return;
                }

                if (!in_array($value, $allowedStatuses, true)) {
                    $fail("The {$attribute} must be one of: ".implode(', ', $allowedStatuses).'.');
                }
            }],
            'filter.service' => 'sometimes|in:LOGISTICS,REGULATORY,ALL',
            'client_type' => 'sometimes|in:OLD,NEW,PROSPECT',
            'search' => 'sometimes|string',
            'as_search' => 'sometimes|string',
            'page' => 'sometimes|integer|min:1',
            'my_page' => 'sometimes|integer|min:1',
            'client_id' => [
                'sometimes',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $isClient = User::role('Client')->where('id', $value)->exists();

                    if (!$isClient) {
                        $fail('The selected client must have a Client role.');
                    }
                },
            ],
        ];
    }
}
