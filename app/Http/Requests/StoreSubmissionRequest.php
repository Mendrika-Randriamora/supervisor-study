<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStudent() ?? false;
    }

    public function rules(): array
    {
        return [
            'text_answer' => ['nullable', 'string', 'max:50000'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'files.*.image' => 'Chaque pièce jointe doit être une image.',
            'files.*.mimes' => 'Formats acceptés : JPG, JPEG, PNG et WEBP.',
            'files.*.max' => 'Chaque image ne peut pas dépasser 10 Mo.',
            'files.max' => 'Vous pouvez joindre au maximum 10 images.',
        ];
    }
}
