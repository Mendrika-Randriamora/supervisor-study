<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Models\Exercise;
use App\Models\Quiz;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TaskRequest extends FormRequest
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
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'theme_id' => ['nullable', 'integer', 'exists:themes,id'],
            'exercise_id' => ['nullable', 'integer', 'exists:exercises,id'],
            'quiz_id' => ['nullable', 'integer', 'exists:quizzes,id'],
            'starts_at' => ['required', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($this->filled('exercise_id') && $this->filled('quiz_id')) {
                    $validator->errors()->add('exercise_id', 'Une tâche ne peut être liée qu’à un seul exercice ou quiz.');
                }

                $studentIds = $this->input('student_ids', []);
                $validStudents = User::students()->active()->whereIn('id', $studentIds)->count();

                if ($validStudents !== count($studentIds)) {
                    $validator->errors()->add('student_ids', 'Un ou plusieurs étudiants sélectionnés sont invalides ou désactivés.');
                }

                if ($this->filled('theme_id')) {
                    $theme = Theme::find($this->input('theme_id'));

                    if ($theme && (int) $theme->subject_id !== (int) $this->input('subject_id')) {
                        $validator->errors()->add('theme_id', 'Le thème doit appartenir à la matière sélectionnée.');
                    }
                }

                $linkedTheme = null;

                if ($this->filled('exercise_id')) {
                    $linkedTheme = Exercise::find($this->input('exercise_id'))?->theme;
                    $field = 'exercise_id';
                } else {
                    $linkedTheme = $this->filled('quiz_id')
                        ? Quiz::find($this->input('quiz_id'))?->theme
                        : null;
                    $field = 'quiz_id';
                }

                if ($linkedTheme && (int) $linkedTheme->subject_id !== (int) $this->input('subject_id')) {
                    $validator->errors()->add($field, 'L’activité liée doit appartenir à la matière sélectionnée.');
                }
            },
        ];
    }
}
