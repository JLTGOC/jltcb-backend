<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $user = $this->route('user');
        $userId = $user ? $user->id : null;

        return [
            'current_password' => [
                'nullable',
                Rule::requiredIf($this->user() && $this->user()->id === $userId),
                'current_password',
            ],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
