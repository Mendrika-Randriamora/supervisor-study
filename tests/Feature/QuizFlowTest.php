<?php

use App\Enums\QuizQuestionType;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\QuizGradingService;

/**
 * @param  array<int, array{type?: QuizQuestionType, points?: float, options: array<int, array{text: string, correct: bool}>}>  $questions
 */
function buildQuiz(User $supervisor, array $questions): Quiz
{
    $quiz = Quiz::factory()->published()->create(['created_by' => $supervisor->id]);

    foreach ($questions as $position => $data) {
        $question = $quiz->questions()->create([
            'question_text' => 'Question '.($position + 1),
            'type' => $data['type'] ?? QuizQuestionType::Single,
            'points' => $data['points'] ?? 2,
            'position' => $position,
        ]);

        foreach ($data['options'] as $optionPosition => $option) {
            $question->options()->create([
                'option_text' => $option['text'],
                'is_correct' => $option['correct'],
                'position' => $optionPosition,
            ]);
        }
    }

    $quiz->recalculateTotalPoints();

    return $quiz->fresh();
}

beforeEach(function () {
    $this->supervisor = User::factory()->supervisor()->create();
    $this->student = User::factory()->create();
});

test('the quiz page does not leak correct answers before submission', function () {
    $quiz = buildQuiz($this->supervisor, [
        ['options' => [
            ['text' => 'Bonne réponse', 'correct' => true],
            ['text' => 'Mauvaise réponse', 'correct' => false],
        ]],
    ]);

    $response = $this->actingAs($this->student)->get(route('student.quizzes.take', $quiz));

    $response->assertOk()
        ->assertSee('Bonne réponse')
        ->assertDontSee('is_correct')
        ->assertDontSee('correctOptions');
});

test('single choice questions are graded exactly', function () {
    $quiz = buildQuiz($this->supervisor, [
        ['points' => 4, 'options' => [
            ['text' => 'A', 'correct' => true],
            ['text' => 'B', 'correct' => false],
        ]],
    ]);

    $question = $quiz->questions->first();
    $correct = $question->options->firstWhere('is_correct', true);

    $this->actingAs($this->student)->post(route('student.quizzes.submit', $quiz), [
        'answers' => [$question->id => $correct->id],
    ])->assertRedirect();

    $attempt = QuizAttempt::first();

    expect((float) $attempt->score)->toBe(4.0)
        ->and((float) $attempt->max_points)->toBe(4.0)
        ->and($attempt->correct_count)->toBe(1)
        ->and($attempt->convertedTo20())->toBe(20.0);
});

test('single choice questions award zero for a wrong choice', function () {
    $quiz = buildQuiz($this->supervisor, [
        ['points' => 4, 'options' => [
            ['text' => 'A', 'correct' => true],
            ['text' => 'B', 'correct' => false],
        ]],
    ]);

    $question = $quiz->questions->first();
    $wrong = $question->options->firstWhere('is_correct', false);

    $this->actingAs($this->student)->post(route('student.quizzes.submit', $quiz), [
        'answers' => [$question->id => $wrong->id],
    ]);

    expect((float) QuizAttempt::first()->score)->toBe(0.0);
});

test('multiple choice questions apply the partial credit formula', function () {
    $quiz = buildQuiz($this->supervisor, [
        ['type' => QuizQuestionType::Multiple, 'points' => 3, 'options' => [
            ['text' => 'A', 'correct' => true],
            ['text' => 'B', 'correct' => true],
            ['text' => 'C', 'correct' => false],
        ]],
    ]);

    $question = $quiz->questions->first();
    $options = $question->options;
    $a = $options->firstWhere('option_text', 'A');
    $b = $options->firstWhere('option_text', 'B');
    $c = $options->firstWhere('option_text', 'C');
    $grader = app(QuizGradingService::class);

    $partial = $grader->grade($quiz, $this->student, [$question->id => [$a->id]]);
    expect((float) $partial->score)->toBe(1.5);

    $zero = $grader->grade($quiz, $this->student, [$question->id => [$a->id, $c->id]]);
    expect((float) $zero->score)->toBe(0.0);

    $full = $grader->grade($quiz, $this->student, [$question->id => [$a->id, $b->id]]);
    expect((float) $full->score)->toBe(3.0)
        ->and($full->correct_count)->toBe(1);
});

test('multiple attempts are preserved and numbered', function () {
    $quiz = buildQuiz($this->supervisor, [
        ['points' => 2, 'options' => [
            ['text' => 'A', 'correct' => true],
            ['text' => 'B', 'correct' => false],
        ]],
    ]);

    $question = $quiz->questions->first();
    $correct = $question->options->firstWhere('is_correct', true);
    $wrong = $question->options->firstWhere('is_correct', false);

    $this->actingAs($this->student)->post(route('student.quizzes.submit', $quiz), [
        'answers' => [$question->id => $wrong->id],
    ]);
    $this->actingAs($this->student)->post(route('student.quizzes.submit', $quiz), [
        'answers' => [$question->id => $correct->id],
    ]);

    $attempts = QuizAttempt::orderBy('attempt_number')->get();

    expect($attempts)->toHaveCount(2)
        ->and($attempts[0]->attempt_number)->toBe(1)
        ->and($attempts[1]->attempt_number)->toBe(2)
        ->and((float) $attempts[0]->score)->toBe(0.0)
        ->and((float) $attempts[1]->score)->toBe(2.0);

    $best = $quiz->bestAttemptFor($this->student);
    expect($best->id)->toBe($attempts[1]->id);
});

test('an overdue quiz cannot be attempted', function () {
    $quiz = buildQuiz($this->supervisor, [
        ['options' => [
            ['text' => 'A', 'correct' => true],
            ['text' => 'B', 'correct' => false],
        ]],
    ]);
    $quiz->forceFill(['due_at' => now()->subDay()])->save();

    $this->actingAs($this->student)
        ->post(route('student.quizzes.start', $quiz))
        ->assertSessionHasErrors('quiz');

    $this->actingAs($this->student)
        ->get(route('student.quizzes.take', $quiz))
        ->assertRedirect(route('student.quizzes.show', $quiz));
});

test('a student cannot view another student attempt result', function () {
    $quiz = buildQuiz($this->supervisor, [
        ['options' => [
            ['text' => 'A', 'correct' => true],
            ['text' => 'B', 'correct' => false],
        ]],
    ]);

    $question = $quiz->questions->first();
    $correct = $question->options->firstWhere('is_correct', true);

    $this->actingAs($this->student)->post(route('student.quizzes.submit', $quiz), [
        'answers' => [$question->id => $correct->id],
    ]);

    $attempt = QuizAttempt::first();
    $other = User::factory()->create();

    $this->actingAs($other)
        ->get(route('student.quizzes.result', ['quiz' => $quiz, 'attempt' => $attempt->id]))
        ->assertNotFound();

    $this->actingAs($this->student)
        ->get(route('student.quizzes.result', ['quiz' => $quiz, 'attempt' => $attempt->id]))
        ->assertOk()
        ->assertSee('Correction détaillée');
});

test('unanswered questions score zero', function () {
    $quiz = buildQuiz($this->supervisor, [
        ['points' => 2, 'options' => [
            ['text' => 'A', 'correct' => true],
            ['text' => 'B', 'correct' => false],
        ]],
    ]);

    $this->actingAs($this->student)->post(route('student.quizzes.submit', $quiz), [
        'answers' => [],
    ]);

    $attempt = QuizAttempt::first();

    expect($attempt)->not->toBeNull()
        ->and((float) $attempt->score)->toBe(0.0)
        ->and($attempt->incorrect_count)->toBe(1);
});

test('quiz options are ordered by position', function () {
    $quiz = buildQuiz($this->supervisor, [
        ['options' => [
            ['text' => 'Z', 'correct' => false],
            ['text' => 'A', 'correct' => true],
        ]],
    ]);

    $question = QuizQuestion::with('options')->first();

    expect($question->options->pluck('option_text')->all())->toBe(['Z', 'A'])
        ->and(QuizOption::count())->toBe(2);
});
