<?php

use App\Enums\ExerciseType;
use App\Enums\QuizQuestionType;
use App\Enums\SubmissionStatus;
use App\Models\Exercise;
use App\Models\ExerciseGrade;
use App\Models\ExerciseSubmission;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\Theme;
use App\Models\User;
use App\Services\ActivityLogger;

beforeEach(function () {
    $this->supervisor = User::factory()->supervisor()->create();
    $this->student = User::factory()->create();

    $this->subject = Subject::factory()->create();
    $this->theme = Theme::factory()->create(['subject_id' => $this->subject->id]);

    $this->exercise = Exercise::factory()->published()->create([
        'theme_id' => $this->theme->id,
        'created_by' => $this->supervisor->id,
        'type' => ExerciseType::Text,
        'due_at' => now()->addDays(2),
    ]);

    $this->quiz = Quiz::factory()->published()->create([
        'theme_id' => $this->theme->id,
        'created_by' => $this->supervisor->id,
        'due_at' => now()->addDays(3),
    ]);
    $question = $this->quiz->questions()->create([
        'question_text' => 'Quelle est la bonne réponse ?',
        'type' => QuizQuestionType::Single,
        'points' => 2,
        'position' => 0,
    ]);
    $question->options()->create(['option_text' => 'Réponse A', 'is_correct' => true, 'position' => 0]);
    $question->options()->create(['option_text' => 'Réponse B', 'is_correct' => false, 'position' => 1]);
    $this->quiz->recalculateTotalPoints();

    $this->submission = ExerciseSubmission::factory()->create([
        'exercise_id' => $this->exercise->id,
        'user_id' => $this->student->id,
        'status' => SubmissionStatus::Graded,
    ]);
    ExerciseGrade::factory()->create([
        'submission_id' => $this->submission->id,
        'graded_by' => $this->supervisor->id,
        'score' => 14,
        'max_points' => 20,
        'comment' => 'Bon travail.',
    ]);

    $this->attempt = QuizAttempt::factory()->create([
        'quiz_id' => $this->quiz->id,
        'user_id' => $this->student->id,
        'attempt_number' => 1,
        'score' => 2,
        'max_points' => 2,
    ]);

    $this->task = Task::factory()->create([
        'created_by' => $this->supervisor->id,
        'subject_id' => $this->subject->id,
        'theme_id' => $this->theme->id,
        'due_at' => now()->addDays(1),
    ]);
    TaskAssignment::factory()->create([
        'task_id' => $this->task->id,
        'user_id' => $this->student->id,
    ]);
});

test('all supervisor pages render', function () {
    $this->actingAs($this->supervisor);

    $routes = [
        route('dashboard'),
        route('supervisor.students.index'),
        route('supervisor.students.create'),
        route('supervisor.students.show', $this->student),
        route('supervisor.students.edit', $this->student),
        route('supervisor.subjects.index'),
        route('supervisor.subjects.create'),
        route('supervisor.subjects.show', $this->subject),
        route('supervisor.subjects.edit', $this->subject),
        route('supervisor.exercises.index'),
        route('supervisor.exercises.create'),
        route('supervisor.exercises.show', $this->exercise),
        route('supervisor.exercises.edit', $this->exercise),
        route('supervisor.submissions.index'),
        route('supervisor.submissions.show', $this->submission),
        route('supervisor.quizzes.index'),
        route('supervisor.quizzes.create'),
        route('supervisor.quizzes.show', $this->quiz),
        route('supervisor.quizzes.edit', $this->quiz),
        route('supervisor.tasks.index'),
        route('supervisor.tasks.create'),
        route('supervisor.tasks.show', $this->task),
        route('supervisor.tasks.edit', $this->task),
        route('supervisor.notifications.create'),
        route('supervisor.history.index'),
        route('calendar.index'),
        route('notifications.index'),
        route('profile.edit'),
    ];

    foreach ($routes as $url) {
        $this->get($url)->assertOk();
    }
});

test('all student pages render', function () {
    $this->actingAs($this->student);

    $routes = [
        route('dashboard'),
        route('student.subjects.index'),
        route('student.subjects.show', $this->subject),
        route('student.exercises.index'),
        route('student.exercises.show', $this->exercise),
        route('student.submissions.show', $this->submission),
        route('student.quizzes.index'),
        route('student.quizzes.show', $this->quiz),
        route('student.quizzes.take', $this->quiz),
        route('student.quizzes.result', ['quiz' => $this->quiz, 'attempt' => $this->attempt->id]),
        route('student.results.index'),
        route('student.results.show', $this->quiz),
        route('student.tasks.index'),
        route('student.tasks.show', $this->task),
        route('student.history.index'),
        route('calendar.index'),
        route('notifications.index'),
        route('profile.edit'),
    ];

    foreach ($routes as $url) {
        $this->get($url)->assertOk();
    }
});

test('the calendar filters by subject and type', function () {
    $this->actingAs($this->student)
        ->get(route('calendar.index', ['subject' => $this->subject->id, 'type' => 'exercise']))
        ->assertOk()
        ->assertSee($this->exercise->title)
        ->assertDontSee($this->quiz->title);
});

test('the history pages show recorded events', function () {
    app(ActivityLogger::class)->log(
        $this->student,
        'exercise_graded',
        'Exercice corrigé : '.$this->exercise->title,
        null,
        $this->submission,
    );

    $this->actingAs($this->student)
        ->get(route('student.history.index'))
        ->assertOk()
        ->assertSee('Exercice corrigé');

    $this->actingAs($this->supervisor)
        ->get(route('supervisor.history.index'))
        ->assertOk()
        ->assertSee($this->student->name);
});

test('the history filters by theme', function () {
    $otherTheme = Theme::factory()->create(['subject_id' => $this->subject->id]);
    $otherExercise = Exercise::factory()->published()->create([
        'theme_id' => $otherTheme->id,
        'created_by' => $this->supervisor->id,
    ]);

    $logger = app(ActivityLogger::class);
    $logger->log($this->student, 'exercise_graded', 'Événement du thème filtré', null, $this->exercise);
    $logger->log($this->student, 'exercise_graded', 'Événement hors thème', null, $otherExercise);

    $this->actingAs($this->supervisor)
        ->get(route('supervisor.history.index', ['theme' => $this->theme->id]))
        ->assertOk()
        ->assertSee('Événement du thème filtré')
        ->assertDontSee('Événement hors thème');
});
