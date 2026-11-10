<?php

namespace App\Services;

use App\Enums\QuizQuestionType;
use App\Enums\SubmissionStatus;
use App\Models\ExerciseSubmission;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class QuizGradingService
{
    /**
     * Grade a quiz attempt server-side and persist every answer.
     *
     * @param  array<int|string, int|string|array<int, int|string>>  $submittedAnswers
     */
    public function grade(Quiz $quiz, User $student, array $submittedAnswers, ?int $durationSeconds = null): QuizAttempt
    {
        if ($quiz->questions()->count() === 0) {
            throw new InvalidArgumentException('Ce quiz ne contient aucune question.');
        }

        return DB::transaction(function () use ($quiz, $student, $submittedAnswers, $durationSeconds): QuizAttempt {
            $quiz->lockForUpdate();

            $questions = $quiz->questions()->with('options')->get();
            $totalPoints = (float) $questions->sum('points');
            $answersToCreate = [];
            $score = 0.0;
            $correctCount = 0;

            foreach ($questions as $question) {
                $rawSelection = $submittedAnswers[$question->id] ?? $submittedAnswers[(string) $question->id] ?? [];
                $selectedIds = collect(is_array($rawSelection) ? $rawSelection : [$rawSelection])
                    ->filter(fn ($id) => $id !== null && $id !== '')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                $validIds = $question->options->pluck('id')->map(fn ($id) => (int) $id);
                $selectedIds = $selectedIds->intersect($validIds)->values();
                $correctIds = $question->options->where('is_correct', true)->pluck('id')->map(fn ($id) => (int) $id);

                $pointsAwarded = 0.0;
                $isCorrect = false;

                if ($question->type === QuizQuestionType::Single) {
                    $isCorrect = $selectedIds->count() === 1
                        && $correctIds->count() === 1
                        && $selectedIds->first() === $correctIds->first();
                    $pointsAwarded = $isCorrect ? (float) $question->points : 0.0;
                } else {
                    $correctSelected = $selectedIds->intersect($correctIds)->count();
                    $incorrectSelected = $selectedIds->diff($correctIds)->count();
                    $totalCorrect = $correctIds->count();

                    if ($totalCorrect > 0) {
                        $ratio = ($correctSelected - $incorrectSelected) / $totalCorrect;
                        $pointsAwarded = max(0, min((float) $question->points, (float) $question->points * $ratio));
                    }

                    $isCorrect = $totalCorrect > 0
                        && $selectedIds->count() === $totalCorrect
                        && $correctSelected === $totalCorrect;
                }

                $pointsAwarded = round($pointsAwarded, 2);
                $score += $pointsAwarded;

                if ($isCorrect) {
                    $correctCount++;
                }

                $answersToCreate[] = [
                    'question' => $question,
                    'selected' => $selectedIds->all(),
                    'points' => $pointsAwarded,
                    'is_correct' => $isCorrect,
                ];
            }

            $attemptNumber = ((int) $quiz->attempts()->where('user_id', $student->id)->max('attempt_number')) + 1;

            $attempt = QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'user_id' => $student->id,
                'attempt_number' => $attemptNumber,
                'score' => round($score, 2),
                'max_points' => round($totalPoints, 2),
                'correct_count' => $correctCount,
                'incorrect_count' => $questions->count() - $correctCount,
                'duration_seconds' => $durationSeconds,
                'submitted_at' => now(),
            ]);

            foreach ($answersToCreate as $answerData) {
                $answer = $attempt->answers()->create([
                    'quiz_question_id' => $answerData['question']->id,
                    'points_awarded' => $answerData['points'],
                    'is_correct' => $answerData['is_correct'],
                ]);
                $answer->selectedOptions()->attach($answerData['selected']);
            }

            return $attempt->load('answers.question.options', 'quiz.theme.subject');
        });
    }

    /**
     * Mark a text/PDF submission as graded in a transaction after creating an immutable grade row.
     */
    public function markSubmissionGraded(ExerciseSubmission $submission): void
    {
        DB::transaction(function () use ($submission): void {
            $submission->forceFill([
                'status' => SubmissionStatus::Graded,
                'graded_at' => now(),
            ])->save();
        });
    }
}
