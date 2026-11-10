<?php

namespace App\Http\Requests;

use App\Enums\QuizQuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class QuizQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSupervisor() ?? false;
    }

    public function rules(): array
    {
        return [
            'question_text' => ['required', 'string', 'max:5000'],
            'type' => ['required', Rule::enum(QuizQuestionType::class)],
            'points' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'explanation' => ['nullable', 'string', 'max:5000'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
            'options' => ['required', 'array', 'min:2', 'max:10'],
            'options.*.text' => ['required', 'string', 'max:500'],
            'options.*.is_correct' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $correctCount = collect($this->input('options', []))
                    ->filter(fn (array $option) => (bool) ($option['is_correct'] ?? false))
                    ->count();

                if ($this->input('type') === QuizQuestionType::Single->value && $correctCount !== 1) {
                    $validator->errors()->add('options', 'Une question à choix unique doit avoir exactement une bonne réponse.');
                }

                if ($this->input('type') === QuizQuestionType::Multiple->value) {
                    $optionCount = count($this->input('options', []));

                    if ($correctCount < 2) {
                        $validator->errors()->add('options', 'Une question à choix multiples doit avoir au moins deux bonnes réponses.');
                    }

                    if ($correctCount >= $optionCount) {
                        $validator->errors()->add('options', 'Laissez au moins une proposition incorrecte pour éviter que tout cocher donne le maximum.');
                    }
                }
            },
        ];
    }
}
