<?php

namespace App\Http\Requests;

use App\Enums\QuizStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSupervisor() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:10000'],
            'theme_id' => ['required', 'integer', 'exists:themes,id'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:300'],
            'published_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:published_at'],
            'status' => ['required', Rule::enum(QuizStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'due_at.after_or_equal' => 'La date limite doit être postérieure à la date de publication.',
        ];
    }
}
