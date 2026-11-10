<?php

use App\Enums\SubmissionStatus;
use App\Models\Exercise;
use App\Models\ExerciseSubmission;
use App\Models\Quiz;
use App\Models\User;

test('guests are redirected to login', function () {
    $this->get('/dashboard')->assertRedirect('/login');
    $this->get('/calendrier')->assertRedirect('/login');
    $this->get('/notifications')->assertRedirect('/login');
});

test('students cannot access the supervisor space', function () {
    $student = User::factory()->create();

    $this->actingAs($student)->get('/superviseur/etudiants')->assertForbidden();
    $this->actingAs($student)->get('/superviseur/exercices')->assertForbidden();
    $this->actingAs($student)->get('/superviseur/taches')->assertForbidden();
});

test('supervisors cannot access the student space', function () {
    $supervisor = User::factory()->supervisor()->create();

    $this->actingAs($supervisor)->get('/etudiant/exercices')->assertForbidden();
    $this->actingAs($supervisor)->get('/etudiant/quiz')->assertForbidden();
    $this->actingAs($supervisor)->get('/etudiant/taches')->assertForbidden();
});

test('inactive users cannot log in', function () {
    $user = User::factory()->inactive()->create([
        'email' => 'inactive@example.com',
        'password' => 'password',
    ]);

    $response = $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('students cannot view another student submission', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $exercise = Exercise::factory()->published()->create();
    $submission = ExerciseSubmission::factory()->create([
        'exercise_id' => $exercise->id,
        'user_id' => $owner->id,
    ]);

    $this->actingAs($intruder)
        ->get(route('student.submissions.show', $submission))
        ->assertForbidden();

    $this->actingAs($owner)
        ->get(route('student.submissions.show', $submission))
        ->assertOk();
});

test('draft exercises and quizzes are not visible to students', function () {
    $student = User::factory()->create();
    $exercise = Exercise::factory()->create();
    $quiz = Quiz::factory()->create();

    $this->actingAs($student)->get(route('student.exercises.show', $exercise))->assertForbidden();
    $this->actingAs($student)->get(route('student.quizzes.show', $quiz))->assertForbidden();
});

test('submission statuses use the expected persisted values', function () {
    expect(SubmissionStatus::Submitted->value)->toBe('submitted')
        ->and(SubmissionStatus::Graded->value)->toBe('graded')
        ->and(SubmissionStatus::RevisionRequested->value)->toBe('revision_requested');
});
