<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreArticleRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'image' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (request()->hasFile('image')) {
                        $file = request()->file('image');
                        if (!$file->isValid()) {
                            return $fail('The uploaded file is invalid.');
                        }
                        if (!in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/jpg'])) {
                            return $fail('The file must be a JPG or PNG image.');
                        }
                        return;
                    }

                    if (is_string($value) && preg_match('/^data:image\/(\w+);base64,/', $value)) {
                        return;
                    }

                    if (request('type') === 'public') {
                        $fail('The image must be a valid file upload or a Base64 encoded string.');
                    }
                },
            ],
        ];
    }
}
