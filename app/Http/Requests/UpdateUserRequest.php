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

        return [
            'first_name' => ['sometimes', 'string'],
            'last_name' => ['sometimes', 'string'],
            'position' => ['sometimes', 'nullable', 'string', 'exists:roles,name'],
            'contact_number' => ['sometimes', 'nullable', 'string', 'size:11', 'regex:/^09\d{9}$/'],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($userId)],
        ];
    }
}
