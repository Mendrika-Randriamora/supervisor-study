<?php

use App\Enums\TaskStatus;
use App\Models\ActivityEvent;
use App\Models\Exercise;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\Theme;
use App\Models\User;
use App\Services\NotificationService;

beforeEach(function () {
    $this->supervisor = User::factory()->supervisor()->create();
    $this->student = User::factory()->create();
    $this->otherStudent = User::factory()->create();
    $this->subject = Subject::factory()->create();
    $this->theme = Theme::factory()->create(['subject_id' => $this->subject->id]);
});

test('a supervisor can create a task assigned to several students', function () {
    $response = $this->actingAs($this->supervisor)->post(route('supervisor.tasks.store'), [
        'title' => 'Réviser les dérivées',
        'description' => 'Faire les exercices 1 à 5.',
        'subject_id' => $this->subject->id,
        'theme_id' => $this->theme->id,
        'starts_at' => now()->toDateString(),
        'due_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
        'priority' => 'high',
        'student_ids' => [$this->student->id, $this->otherStudent->id],
    ]);

    $task = Task::first();

    expect($task)->not->toBeNull()
        ->and($task->assignments)->toHaveCount(2)
        ->and($task->priority->value)->toBe('high')
        ->and($this->student->notifications()->count())->toBe(1)
        ->and($this->otherStudent->notifications()->count())->toBe(1)
        ->and(ActivityEvent::where('type', 'task_assigned')->count())->toBe(2);

    $response->assertRedirect(route('supervisor.tasks.show', $task));
});

test('a task cannot be linked to both an exercise and a quiz', function () {
    $exercise = Exercise::factory()->create(['theme_id' => $this->theme->id]);
    $quiz = Quiz::factory()->create(['theme_id' => $this->theme->id]);

    $this->actingAs($this->supervisor)->post(route('supervisor.tasks.store'), [
        'title' => 'Tâche invalide',
        'subject_id' => $this->subject->id,
        'exercise_id' => $exercise->id,
        'quiz_id' => $quiz->id,
        'starts_at' => now()->toDateString(),
        'priority' => 'normal',
        'student_ids' => [$this->student->id],
    ])->assertSessionHasErrors('exercise_id');

    expect(Task::count())->toBe(0);
});

test('a student can move a task through statuses and completion date is preserved', function () {
    $task = Task::factory()->create([
        'created_by' => $this->supervisor->id,
        'subject_id' => $this->subject->id,
    ]);
    $assignment = TaskAssignment::factory()->create([
        'task_id' => $task->id,
        'user_id' => $this->student->id,
        'status' => TaskStatus::Todo,
    ]);

    $this->actingAs($this->student)->patch(route('student.tasks.status', $task), [
        'status' => 'in_progress',
    ])->assertSessionHasNoErrors();

    expect($assignment->refresh()->status)->toBe(TaskStatus::InProgress);

    $this->actingAs($this->student)->patch(route('student.tasks.status', $task), [
        'status' => 'completed',
    ])->assertSessionHasNoErrors();

    $assignment->refresh();
    $completedAt = $assignment->completed_at;

    expect($assignment->status)->toBe(TaskStatus::Completed)
        ->and($completedAt)->not->toBeNull();

    $this->actingAs($this->student)->patch(route('student.tasks.status', $task), [
        'status' => 'todo',
    ]);

    expect($assignment->refresh()->completed_at)->toBeNull();
});

test('overdue status is derived without being persisted', function () {
    $task = Task::factory()->create([
        'created_by' => $this->supervisor->id,
        'subject_id' => $this->subject->id,
        'due_at' => now()->subDay(),
    ]);
    $assignment = TaskAssignment::factory()->create([
        'task_id' => $task->id,
        'user_id' => $this->student->id,
        'status' => TaskStatus::Todo,
    ]);

    expect($assignment->effectiveStatus())->toBe(TaskStatus::Overdue)
        ->and($assignment->refresh()->status)->toBe(TaskStatus::Todo);

    $this->actingAs($this->student)->patch(route('student.tasks.status', $task), [
        'status' => 'completed',
    ]);

    expect($assignment->refresh()->effectiveStatus())->toBe(TaskStatus::Completed);
});

test('a student cannot update a task that is not assigned to them', function () {
    $task = Task::factory()->create([
        'created_by' => $this->supervisor->id,
        'subject_id' => $this->subject->id,
    ]);

    $this->actingAs($this->student)
        ->patch(route('student.tasks.status', $task), ['status' => 'completed'])
        ->assertForbidden();
});

test('a supervisor can send a manual notification to all active students', function () {
    $inactive = User::factory()->inactive()->create();

    $this->actingAs($this->supervisor)->post(route('supervisor.notifications.store'), [
        'title' => 'Rappel général',
        'message' => 'Pensez à rendre vos exercices.',
        'audience' => 'all',
    ])->assertSessionHasNoErrors();

    expect($this->student->notifications()->count())->toBe(1)
        ->and($this->otherStudent->notifications()->count())->toBe(1)
        ->and($inactive->notifications()->count())->toBe(0);
});

test('a supervisor can target selected students only', function () {
    $this->actingAs($this->supervisor)->post(route('supervisor.notifications.store'), [
        'title' => 'Message ciblé',
        'message' => 'Uniquement pour vous.',
        'audience' => 'selected',
        'student_ids' => [$this->student->id],
    ])->assertSessionHasNoErrors();

    expect($this->student->notifications()->count())->toBe(1)
        ->and($this->otherStudent->notifications()->count())->toBe(0);
});

test('deadline reminders are sent once per exercise', function () {
    Exercise::factory()->published()->create([
        'created_by' => $this->supervisor->id,
        'theme_id' => $this->theme->id,
        'due_at' => now()->addDay(),
    ]);

    $this->artisan('app:send-deadline-reminders')->assertSuccessful();
    $this->artisan('app:send-deadline-reminders')->assertSuccessful();

    expect($this->student->notifications()->count())->toBe(1)
        ->and($this->otherStudent->notifications()->count())->toBe(1);
});

test('deadline reminders include quizzes without attempts', function () {
    Quiz::factory()->published()->create([
        'created_by' => $this->supervisor->id,
        'theme_id' => $this->theme->id,
        'due_at' => now()->addDay(),
    ]);

    $this->artisan('app:send-deadline-reminders')->assertSuccessful();
    $this->artisan('app:send-deadline-reminders')->assertSuccessful();

    expect($this->student->notifications()->count())->toBe(1)
        ->and($this->otherStudent->notifications()->count())->toBe(1);
});

test('a notification can be marked as read', function () {
    app(NotificationService::class)->notify($this->student, 'Titre', 'Message', route('notifications.index'));

    $notification = $this->student->notifications()->first();

    $this->actingAs($this->student)
        ->patch(route('notifications.read', $notification->id))
        ->assertRedirect(route('notifications.index'));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('all notifications can be marked as read', function () {
    $service = app(NotificationService::class);
    $service->notify($this->student, 'Titre 1', 'Message 1', route('notifications.index'));
    $service->notify($this->student, 'Titre 2', 'Message 2', route('notifications.index'));

    expect($this->student->unreadNotifications()->count())->toBe(2);

    $this->actingAs($this->student)
        ->patch(route('notifications.read-all'))
        ->assertRedirect();

    expect($this->student->unreadNotifications()->count())->toBe(0);
});
