<?php

use App\Enums\ExerciseType;
use App\Enums\SubmissionStatus;
use App\Models\ActivityEvent;
use App\Models\Exercise;
use App\Models\ExerciseSubmission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->supervisor = User::factory()->supervisor()->create();
    $this->student = User::factory()->create();
    $this->exercise = Exercise::factory()->published()->create([
        'created_by' => $this->supervisor->id,
        'type' => ExerciseType::Text,
        'max_points' => 20,
    ]);
});

test('a student can submit a text answer', function () {
    $response = $this->actingAs($this->student)->post(route('student.exercises.submit', $this->exercise), [
        'text_answer' => 'Voici ma réponse détaillée.',
    ]);

    $submission = ExerciseSubmission::first();

    expect($submission)->not->toBeNull()
        ->and($submission->user_id)->toBe($this->student->id)
        ->and($submission->version)->toBe(1)
        ->and($submission->status)->toBe(SubmissionStatus::Submitted)
        ->and($submission->text_answer)->toBe('Voici ma réponse détaillée.');

    $response->assertRedirect(route('student.submissions.show', $submission));

    expect(ActivityEvent::where('type', 'exercise_submitted')->count())->toBe(1)
        ->and($this->supervisor->notifications()->count())->toBe(1);
});

test('an empty text answer is rejected', function () {
    $this->actingAs($this->student)->post(route('student.exercises.submit', $this->exercise), [
        'text_answer' => '   ',
    ])->assertSessionHasErrors('text_answer');

    expect(ExerciseSubmission::count())->toBe(0);
});

test('a student cannot submit twice unless a revision is requested', function () {
    ExerciseSubmission::factory()->create([
        'exercise_id' => $this->exercise->id,
        'user_id' => $this->student->id,
        'version' => 1,
        'status' => SubmissionStatus::Submitted,
    ]);

    $this->actingAs($this->student)->post(route('student.exercises.submit', $this->exercise), [
        'text_answer' => 'Deuxième envoi.',
    ])->assertSessionHasErrors();

    expect(ExerciseSubmission::count())->toBe(1);
});

test('a requested revision allows a new version', function () {
    $first = ExerciseSubmission::factory()->create([
        'exercise_id' => $this->exercise->id,
        'user_id' => $this->student->id,
        'version' => 1,
        'status' => SubmissionStatus::RevisionRequested,
        'text_answer' => 'Première version',
    ]);

    $this->actingAs($this->student)->post(route('student.exercises.submit', $this->exercise), [
        'text_answer' => 'Version corrigée',
    ]);

    $versions = ExerciseSubmission::orderBy('version')->get();

    expect($versions)->toHaveCount(2)
        ->and($versions[0]->id)->toBe($first->id)
        ->and($versions[1]->version)->toBe(2)
        ->and($versions[1]->text_answer)->toBe('Version corrigée');
});

test('a pdf exercise requires at least one image', function () {
    $exercise = Exercise::factory()->published()->pdf()->create(['created_by' => $this->supervisor->id]);

    $this->actingAs($this->student)->post(route('student.exercises.submit', $exercise), [
        'files' => [],
    ])->assertSessionHasErrors('files');
});

test('a student can upload several images for a pdf exercise', function () {
    Storage::fake('local');
    $exercise = Exercise::factory()->published()->pdf()->create(['created_by' => $this->supervisor->id]);

    $this->actingAs($this->student)->post(route('student.exercises.submit', $exercise), [
        'files' => [
            UploadedFile::fake()->image('page-1.jpg', 800, 1000),
            UploadedFile::fake()->image('page-2.png', 800, 1000),
        ],
    ])->assertSessionHasNoErrors();

    $submission = ExerciseSubmission::first();

    expect($submission)->not->toBeNull()
        ->and($submission->files)->toHaveCount(2);

    foreach ($submission->files as $file) {
        Storage::disk('local')->assertExists($file->path);
    }
});

test('non image files are rejected', function () {
    Storage::fake('local');
    $exercise = Exercise::factory()->published()->pdf()->create(['created_by' => $this->supervisor->id]);

    $this->actingAs($this->student)->post(route('student.exercises.submit', $exercise), [
        'files' => [UploadedFile::fake()->create('document.pdf', 100, 'application/pdf')],
    ])->assertSessionHasErrors('files.0');
});

test('a supervisor can grade a submission and the student is notified', function () {
    $submission = ExerciseSubmission::factory()->create([
        'exercise_id' => $this->exercise->id,
        'user_id' => $this->student->id,
        'status' => SubmissionStatus::Submitted,
    ]);

    $this->actingAs($this->supervisor)->post(route('supervisor.submissions.grade', $submission), [
        'score' => 15,
        'comment' => 'Bon travail.',
    ])->assertSessionHasNoErrors();

    $submission->refresh();
    $grade = $submission->latestGrade;

    expect($submission->status)->toBe(SubmissionStatus::Graded)
        ->and($submission->graded_at)->not->toBeNull()
        ->and($grade)->not->toBeNull()
        ->and((float) $grade->score)->toBe(15.0)
        ->and($grade->convertedTo20())->toBe(15.0)
        ->and($this->student->notifications()->count())->toBe(1)
        ->and(ActivityEvent::where('type', 'exercise_graded')->count())->toBe(1);
});

test('a grade above the exercise maximum is rejected', function () {
    $submission = ExerciseSubmission::factory()->create([
        'exercise_id' => $this->exercise->id,
        'user_id' => $this->student->id,
    ]);

    $this->actingAs($this->supervisor)->post(route('supervisor.submissions.grade', $submission), [
        'score' => 25,
        'comment' => 'Trop haut.',
    ])->assertSessionHasErrors('score');

    expect($submission->fresh()->latestGrade)->toBeNull();
});

test('a supervisor can request a new answer', function () {
    $submission = ExerciseSubmission::factory()->create([
        'exercise_id' => $this->exercise->id,
        'user_id' => $this->student->id,
        'status' => SubmissionStatus::Submitted,
    ]);

    $this->actingAs($this->supervisor)->post(route('supervisor.submissions.revision', $submission), [
        'comment' => 'Merci de détailler le calcul.',
    ])->assertSessionHasNoErrors();

    $submission->refresh();

    expect($submission->status)->toBe(SubmissionStatus::RevisionRequested)
        ->and($submission->revision_comment)->toBe('Merci de détailler le calcul.')
        ->and($this->student->notifications()->count())->toBe(1)
        ->and(ActivityEvent::where('type', 'revision_requested')->count())->toBe(1);
});
