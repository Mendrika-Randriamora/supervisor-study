<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSupervisor() ?? false;
    }

    public function rules(): array
    {
        $theme = $this->route('theme');
        $subjectId = $theme?->subject_id ?? $this->route('subject')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:160',
                Rule::unique('themes', 'name')
                    ->where(fn ($query) => $query->where('subject_id', $subjectId))
                    ->ignore($theme?->id),
            ],
            'description' => ['nullable', 'string', 'max:3000'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }
}
