<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $userId = $this->route('user') ? $this->route('user')->id : null;
        $authUser = $this->user();

        return [
            'first_name' => ['sometimes', 'string'],
            'last_name' => ['sometimes', 'string'],
            'position' => [
                'sometimes',
                'nullable',
                'string',
                'exists:roles,name',
                Rule::prohibitedIf(! $authUser || ! $authUser->hasRole('IT')),
            ],
            'contact_number' => ['sometimes', 'nullable', 'string', 'size:11', 'regex:/^09\d{9}$/', Rule::unique('users', 'contact_number')->ignore($userId)],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'username' => ['sometimes', 'nullable', 'string', Rule::unique('users', 'username')->ignore($userId)],
        ];
    }
}
