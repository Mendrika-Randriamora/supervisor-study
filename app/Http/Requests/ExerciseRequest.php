<?php

namespace App\Http\Requests;

use App\Enums\ExerciseStatus;
use App\Enums\ExerciseType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExerciseRequest extends FormRequest
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
            'type' => ['required', Rule::enum(ExerciseType::class)],
            'max_points' => ['required', 'numeric', 'min:0.01', 'max:1000'],
            'published_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:published_at'],
            'status' => ['required', Rule::enum(ExerciseStatus::class)],
            'document' => ['nullable', 'file', 'mimetypes:application/pdf', 'mimes:pdf', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'document.mimetypes' => 'Le document doit être un fichier PDF valide.',
            'document.max' => 'Le PDF ne peut pas dépasser 10 Mo.',
            'due_at.after_or_equal' => 'La date limite doit être postérieure à la date de publication.',
        ];
    }
}
