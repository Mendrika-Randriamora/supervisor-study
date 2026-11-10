<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnnotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSupervisor() ?? false;
    }

    public function rules(): array
    {
        return [
            'data' => ['required', 'array'],
            'data.paths' => ['nullable', 'array'],
            'data.rects' => ['nullable', 'array'],
            'data.comments' => ['nullable', 'array'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
