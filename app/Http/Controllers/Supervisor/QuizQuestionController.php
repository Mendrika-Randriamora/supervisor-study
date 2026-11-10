<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\QuizStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuizQuestionRequest;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class QuizQuestionController extends Controller
{
    public function store(QuizQuestionRequest $request, Quiz $quiz): RedirectResponse
    {
        if ($error = $this->editabilityError($quiz)) {
            return back()->withErrors(['quiz' => $error]);
        }

        $data = $request->validated();

        DB::transaction(function () use ($quiz, $data): void {
            $question = $quiz->questions()->create([
                'question_text' => $data['question_text'],
                'type' => $data['type'],
                'points' => $data['points'],
                'explanation' => $data['explanation'] ?? null,
                'position' => $data['position'] ?? ($quiz->questions()->max('position') + 1),
            ]);

            $this->replaceOptions($question, $data['options']);
            $quiz->recalculateTotalPoints();
        });

        return back()->with('success', 'Question ajoutée.');
    }

    public function update(QuizQuestionRequest $request, Quiz $quiz, QuizQuestion $question): RedirectResponse
    {
        abort_unless($question->quiz_id === $quiz->id, 404);

        if ($error = $this->editabilityError($quiz)) {
            return back()->withErrors(['quiz' => $error]);
        }

        $data = $request->validated();

        DB::transaction(function () use ($question, $data, $quiz): void {
            $question->update([
                'question_text' => $data['question_text'],
                'type' => $data['type'],
                'points' => $data['points'],
                'explanation' => $data['explanation'] ?? null,
                'position' => $data['position'] ?? $question->position,
            ]);

            $question->options()->delete();
            $this->replaceOptions($question, $data['options']);
            $quiz->recalculateTotalPoints();
        });

        return back()->with('success', 'Question mise à jour.');
    }

    public function destroy(Quiz $quiz, QuizQuestion $question): RedirectResponse
    {
        abort_unless($question->quiz_id === $quiz->id, 404);

        if ($error = $this->editabilityError($quiz)) {
            return back()->withErrors(['quiz' => $error]);
        }

        DB::transaction(function () use ($quiz, $question): void {
            $question->delete();
            $quiz->recalculateTotalPoints();
        });

        return back()->with('success', 'Question supprimée.');
    }

    /**
     * @param  array<int, array{text: string, is_correct?: bool|int|string}>  $options
     */
    private function replaceOptions(QuizQuestion $question, array $options): void
    {
        foreach (array_values($options) as $index => $option) {
            $question->options()->create([
                'option_text' => $option['text'],
                'is_correct' => (bool) ($option['is_correct'] ?? false),
                'position' => $index,
            ]);
        }
    }

    private function editabilityError(Quiz $quiz): ?string
    {
        if ($quiz->attempts()->exists()) {
            return 'Ce quiz possède déjà des tentatives. Ses questions ne peuvent plus être modifiées afin de préserver les résultats.';
        }

        if ($quiz->status === QuizStatus::Published) {
            return 'Archivez ou repassez le quiz en brouillon avant de modifier ses questions.';
        }

        return null;
    }
}
