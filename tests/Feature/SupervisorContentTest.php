<?php

use App\Enums\ExerciseStatus;
use App\Enums\QuizStatus;
use App\Models\ActivityEvent;
use App\Models\Exercise;
use App\Models\ExerciseSubmission;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\SubmissionFile;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->supervisor = User::factory()->supervisor()->create();
    $this->student = User::factory()->create();
});

test('a supervisor can create a subject and a theme', function () {
    $this->actingAs($this->supervisor)->post(route('supervisor.subjects.store'), [
        'name' => 'Mathématiques',
        'code' => 'MATH',
        'description' => 'Analyse, algèbre et géométrie.',
        'color' => '#1e4d6b',
        'position' => 1,
    ])->assertSessionHasNoErrors();

    $subject = Subject::where('name', 'Mathématiques')->firstOrFail();

    $this->actingAs($this->supervisor)->post(route('supervisor.themes.store', $subject), [
        'name' => 'Dérivation',
        'description' => 'Règles de dérivation.',
        'position' => 1,
    ])->assertSessionHasNoErrors();

    expect($subject->themes()->where('name', 'Dérivation')->exists())->toBeTrue();
});

test('a supervisor can publish a pdf exercise with a document and notify active students', function () {
    Storage::fake('local');
    $theme = Theme::factory()->create();

    $this->actingAs($this->supervisor)->post(route('supervisor.exercises.store'), [
        'title' => 'Devoir sur les dérivées',
        'description' => 'Traiter les exercices 1 à 5.',
        'theme_id' => $theme->id,
        'type' => 'pdf',
        'max_points' => 20,
        'status' => 'published',
        'published_at' => now()->subMinute()->toDateTimeString(),
        'due_at' => now()->addDays(3)->toDateTimeString(),
        'document' => UploadedFile::fake()->create('devoir.pdf', 200, 'application/pdf'),
    ])->assertSessionHasNoErrors();

    $exercise = Exercise::where('title', 'Devoir sur les dérivées')->firstOrFail();

    expect($exercise->status)->toBe(ExerciseStatus::Published)
        ->and($exercise->attachments)->toHaveCount(1)
        ->and($this->student->notifications()->count())->toBe(1);

    Storage::disk('local')->assertExists($exercise->attachments->first()->path);
});

test('a supervisor can create a quiz, add a question and publish it', function () {
    $theme = Theme::factory()->create();

    $this->actingAs($this->supervisor)->post(route('supervisor.quizzes.store'), [
        'title' => 'Quiz sur la dérivation',
        'description' => 'Vérification des acquis.',
        'theme_id' => $theme->id,
        'status' => 'draft',
        'time_limit_minutes' => 15,
        'published_at' => null,
        'due_at' => null,
    ])->assertSessionHasNoErrors();

    $quiz = Quiz::where('title', 'Quiz sur la dérivation')->firstOrFail();
    expect($quiz->status)->toBe(QuizStatus::Draft);

    $this->actingAs($this->supervisor)->post(route('supervisor.quizzes.questions.store', $quiz), [
        'question_text' => 'Quelle est la dérivée de x² ?',
        'type' => 'single',
        'points' => 2,
        'position' => 0,
        'explanation' => 'La règle de puissance donne 2x.',
        'options' => [
            ['text' => '2x', 'is_correct' => 1],
            ['text' => 'x', 'is_correct' => 0],
        ],
    ])->assertSessionHasNoErrors();

    $quiz->refresh();

    expect($quiz->questions()->count())->toBe(1)
        ->and((float) $quiz->total_points)->toBe(2.0);

    $this->actingAs($this->supervisor)
        ->patch(route('supervisor.quizzes.publish', $quiz))
        ->assertSessionHasNoErrors();

    expect($quiz->fresh()->status)->toBe(QuizStatus::Published)
        ->and($this->student->notifications()->count())->toBe(1);
});

test('image annotations are stored per file and never modify the original image', function () {
    Storage::fake('local');
    Storage::disk('local')->put('submissions/copie-1.jpg', 'original-image-bytes');
    Storage::disk('local')->put('submissions/copie-2.jpg', 'second-image-bytes');

    $submission = ExerciseSubmission::factory()->create(['user_id' => $this->student->id]);
    $firstFile = SubmissionFile::factory()->create([
        'submission_id' => $submission->id,
        'path' => 'submissions/copie-1.jpg',
    ]);
    $secondFile = SubmissionFile::factory()->create([
        'submission_id' => $submission->id,
        'path' => 'submissions/copie-2.jpg',
    ]);

    $this->actingAs($this->supervisor)->post(
        route('supervisor.submissions.annotate', [$submission, $firstFile]),
        [
            'data' => [
                'paths' => [
                    ['points' => [[0.1, 0.2], [0.2, 0.3]], 'color' => '#b42318', 'width' => 2],
                ],
                'rects' => [],
                'comments' => [],
            ],
            'comment' => 'Attention à cette étape.',
        ],
    )->assertSessionHasNoErrors();

    expect($firstFile->annotations()->count())->toBe(1)
        ->and($secondFile->annotations()->count())->toBe(0)
        ->and(Storage::disk('local')->get('submissions/copie-1.jpg'))->toBe('original-image-bytes')
        ->and($firstFile->annotations()->first()->comment)->toBe('Attention à cette étape.')
        ->and($this->student->notifications()->count())->toBe(1)
        ->and(ActivityEvent::where('type', 'comment_added')->count())->toBe(1);
});
