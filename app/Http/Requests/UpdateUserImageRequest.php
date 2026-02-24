<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserImageRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'image' => ['required', function ($attribute, $value, $fail) {
                // If a file is uploaded, validate file type and size
                if ($this->hasFile('image')) {
                    $file = $this->file('image');
                    if (! $file->isValid()) {
                        return $fail('Uploaded image is invalid.');
                    }

                    $allowed = ['jpeg', 'jpg', 'png', 'gif', 'webp'];
                    $ext = strtolower($file->getClientOriginalExtension());
                    if (! in_array($ext, $allowed)) {
                        return $fail('Unsupported image type.');
                    }

                    if ($file->getSize() > 2 * 1024 * 1024) {
                        return $fail('Image may not be greater than 2048 kilobytes.');
                    }

                    return;
                }

                // Otherwise expect a base64 data URI
                $base64 = $this->input('image');
                if (! is_string($base64) || ! preg_match('/^data:image\/(\w+);base64,/', $base64, $matches)) {
                    return $fail('The image must be a file upload or a base64-encoded image.');
                }

                $extension = strtolower($matches[1]);
                if ($extension === 'jpeg') $extension = 'jpg';
                $allowed = ['jpg', 'png', 'gif', 'webp'];
                if (! in_array($extension, $allowed)) {
                    return $fail('Unsupported image type.');
                }

                $data = substr($base64, strpos($base64, ',') + 1);
                $decoded = base64_decode($data);
                if ($decoded === false) {
                    return $fail('The base64 image is invalid.');
                }

                if (strlen($decoded) > 2 * 1024 * 1024) {
                    return $fail('Image may not be greater than 2048 kilobytes.');
                }
            }],
        ];
    }
}
