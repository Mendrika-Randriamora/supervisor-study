<?php

use App\Enums\SubmissionStatus;
use App\Enums\TaskStatus;
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
use App\Services\StatisticsService;

beforeEach(function () {
    $this->supervisor = User::factory()->supervisor()->create();
    $this->student = User::factory()->create();
    $this->subject = Subject::factory()->create();
    $this->theme = Theme::factory()->create(['subject_id' => $this->subject->id]);
});

test('the average uses the latest graded submission version', function () {
    $exercise = Exercise::factory()->published()->create([
        'theme_id' => $this->theme->id,
        'created_by' => $this->supervisor->id,
        'max_points' => 20,
    ]);

    $first = ExerciseSubmission::factory()->create([
        'exercise_id' => $exercise->id,
        'user_id' => $this->student->id,
        'version' => 1,
        'status' => SubmissionStatus::Graded,
    ]);
    ExerciseGrade::factory()->create([
        'submission_id' => $first->id,
        'graded_by' => $this->supervisor->id,
        'score' => 8,
        'max_points' => 20,
        'graded_at' => now()->subDays(2),
    ]);

    $second = ExerciseSubmission::factory()->create([
        'exercise_id' => $exercise->id,
        'user_id' => $this->student->id,
        'version' => 2,
        'status' => SubmissionStatus::Graded,
    ]);
    ExerciseGrade::factory()->create([
        'submission_id' => $second->id,
        'graded_by' => $this->supervisor->id,
        'score' => 16,
        'max_points' => 20,
        'graded_at' => now()->subDay(),
    ]);

    $overview = app(StatisticsService::class)->studentOverview($this->student);

    expect($overview['average'])->toBe(16.0)
        ->and($overview['graded_count'])->toBe(1)
        ->and($overview['exercises']['graded'])->toBe(1);
});

test('the average uses the best quiz attempt', function () {
    $quiz = Quiz::factory()->published()->create([
        'theme_id' => $this->theme->id,
        'created_by' => $this->supervisor->id,
        'total_points' => 10,
    ]);

    QuizAttempt::factory()->create([
        'quiz_id' => $quiz->id,
        'user_id' => $this->student->id,
        'attempt_number' => 1,
        'score' => 4,
        'max_points' => 10,
    ]);
    QuizAttempt::factory()->create([
        'quiz_id' => $quiz->id,
        'user_id' => $this->student->id,
        'attempt_number' => 2,
        'score' => 9,
        'max_points' => 10,
    ]);

    $overview = app(StatisticsService::class)->studentOverview($this->student);

    expect($overview['average'])->toBe(18.0)
        ->and($overview['graded_count'])->toBe(1)
        ->and($overview['quizzes']['attempts'])->toBe(2)
        ->and($overview['quizzes']['attempted'])->toBe(1);
});

test('only graded activities are included in averages', function () {
    $exercise = Exercise::factory()->published()->create([
        'theme_id' => $this->theme->id,
        'created_by' => $this->supervisor->id,
    ]);

    ExerciseSubmission::factory()->create([
        'exercise_id' => $exercise->id,
        'user_id' => $this->student->id,
        'status' => SubmissionStatus::Submitted,
    ]);

    $overview = app(StatisticsService::class)->studentOverview($this->student);

    expect($overview['average'])->toBeNull()
        ->and($overview['graded_count'])->toBe(0);
});

test('tasks do not affect the academic average', function () {
    $exercise = Exercise::factory()->published()->create([
        'theme_id' => $this->theme->id,
        'created_by' => $this->supervisor->id,
    ]);
    $submission = ExerciseSubmission::factory()->create([
        'exercise_id' => $exercise->id,
        'user_id' => $this->student->id,
        'status' => SubmissionStatus::Graded,
    ]);
    ExerciseGrade::factory()->create([
        'submission_id' => $submission->id,
        'graded_by' => $this->supervisor->id,
        'score' => 10,
        'max_points' => 20,
    ]);

    $task = Task::factory()->create([
        'created_by' => $this->supervisor->id,
        'subject_id' => $this->subject->id,
    ]);
    TaskAssignment::factory()->create([
        'task_id' => $task->id,
        'user_id' => $this->student->id,
        'status' => TaskStatus::Completed,
        'completed_at' => now(),
    ]);

    $overview = app(StatisticsService::class)->studentOverview($this->student);

    expect($overview['average'])->toBe(10.0)
        ->and($overview['tasks']['completed'])->toBe(1)
        ->and($overview['tasks']['rate'])->toBe(100.0);
});

test('subject statistics aggregate exercises quizzes and tasks', function () {
    $exercise = Exercise::factory()->published()->create([
        'theme_id' => $this->theme->id,
        'created_by' => $this->supervisor->id,
    ]);
    Exercise::factory()->published()->create([
        'theme_id' => $this->theme->id,
        'created_by' => $this->supervisor->id,
    ]);
    $quiz = Quiz::factory()->published()->create([
        'theme_id' => $this->theme->id,
        'created_by' => $this->supervisor->id,
    ]);

    $submission = ExerciseSubmission::factory()->create([
        'exercise_id' => $exercise->id,
        'user_id' => $this->student->id,
        'status' => SubmissionStatus::Graded,
    ]);
    ExerciseGrade::factory()->create([
        'submission_id' => $submission->id,
        'graded_by' => $this->supervisor->id,
        'score' => 12,
        'max_points' => 20,
    ]);
    QuizAttempt::factory()->create([
        'quiz_id' => $quiz->id,
        'user_id' => $this->student->id,
        'score' => 6,
        'max_points' => 10,
    ]);

    $task = Task::factory()->create([
        'created_by' => $this->supervisor->id,
        'subject_id' => $this->subject->id,
    ]);
    TaskAssignment::factory()->create([
        'task_id' => $task->id,
        'user_id' => $this->student->id,
        'status' => TaskStatus::Completed,
        'completed_at' => now(),
    ]);

    $overview = app(StatisticsService::class)->studentOverview($this->student);
    $subject = $overview['subjects']->firstWhere('id', $this->subject->id);

    expect($subject)->not->toBeNull()
        ->and($subject['exercises_total'])->toBe(2)
        ->and($subject['exercises_submitted'])->toBe(1)
        ->and($subject['quizzes_total'])->toBe(1)
        ->and($subject['quizzes_attempted'])->toBe(1)
        ->and($subject['tasks_total'])->toBe(1)
        ->and($subject['tasks_completed'])->toBe(1)
        ->and($subject['average'])->toBe(12.0);
});
