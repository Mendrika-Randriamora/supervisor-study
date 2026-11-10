<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\Quiz;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use App\Notifications\PlatformNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function notify(
        User $user,
        string $title,
        string $message,
        string $url,
        string $icon = 'bi-bell',
        string $type = 'info',
        ?string $key = null,
    ): void {
        Notification::send($user, new PlatformNotification($title, $message, $url, $icon, $type, $key));
    }

    /** @param  Collection<int, User>  $users */
    public function notifyMany(
        Collection $users,
        string $title,
        string $message,
        string $url,
        string $icon = 'bi-bell',
        string $type = 'info',
        ?string $key = null,
    ): void {
        $users->each(fn (User $user) => $this->notify($user, $title, $message, $url, $icon, $type, $key));
    }

    public function hasNotificationWithKey(User $user, string $key): bool
    {
        return $user->notifications()->where('data->key', $key)->exists();
    }

    public function exercisePublished(Exercise $exercise): void
    {
        $students = User::students()->active()->get();
        $this->notifyMany(
            $students,
            'Nouvel exercice publié',
            $exercise->title,
            route('student.exercises.show', $exercise),
            'bi-journal-check',
            'exercise',
        );
    }

    public function quizPublished(Quiz $quiz): void
    {
        $students = User::students()->active()->get();
        $this->notifyMany(
            $students,
            'Nouveau quiz disponible',
            $quiz->title,
            route('student.quizzes.show', $quiz),
            'bi-patch-question',
            'quiz',
        );
    }

    public function taskAssigned(Task $task, User $student): void
    {
        $this->notify(
            $student,
            'Nouvelle tâche attribuée',
            $task->title,
            route('student.tasks.show', $task),
            'bi-list-task',
            'task',
        );
    }

    public function submissionReceived(User $supervisor, User $student, string $activityTitle, string $url): void
    {
        $this->notify(
            $supervisor,
            'Nouvelle soumission reçue',
            $student->name.' — '.$activityTitle,
            $url,
            'bi-inbox',
            'submission',
        );
    }

    public function graded(User $student, string $activityTitle, string $url, ?string $comment = null): void
    {
        $message = $activityTitle;

        if ($comment) {
            $message .= ' — '.str($comment)->limit(120);
        }

        $this->notify($student, 'Nouvelle note disponible', $message, $url, 'bi-award', 'grade');
    }

    public function revisionRequested(User $student, string $activityTitle, string $url, ?string $comment = null): void
    {
        $this->notify(
            $student,
            'Nouvelle réponse demandée',
            $activityTitle.($comment ? ' — '.str($comment)->limit(120) : ''),
            $url,
            'bi-arrow-repeat',
            'revision',
        );
    }

    public function commentAdded(User $student, string $activityTitle, string $url, string $comment): void
    {
        $this->notify(
            $student,
            'Nouveau commentaire',
            $activityTitle.' — '.str($comment)->limit(120),
            $url,
            'bi-chat-left-text',
            'comment',
        );
    }

    /** @param  Collection<int, User>  $students */
    public function manual(Collection $students, string $title, string $message): void
    {
        $this->notifyMany($students, $title, $message, route('notifications.index'), 'bi-megaphone', 'manual');
    }

    public function deadlineReminders(): int
    {
        $sent = 0;
        $limit = now()->addHours(48);

        $exercises = Exercise::published()
            ->whereBetween('due_at', [now(), $limit])
            ->with('theme.subject')
            ->get();

        $quizzes = Quiz::published()
            ->whereBetween('due_at', [now(), $limit])
            ->with('theme.subject')
            ->get();

        foreach (User::students()->active()->get() as $student) {
            foreach ($exercises as $exercise) {
                if ($exercise->submissionFor($student)) {
                    continue;
                }

                $key = 'deadline:exercise:'.$exercise->id;

                if ($this->hasNotificationWithKey($student, $key)) {
                    continue;
                }

                $this->notify(
                    $student,
                    'Échéance proche',
                    $exercise->title.' — à rendre avant le '.$exercise->due_at->format('d/m/Y H:i'),
                    route('student.exercises.show', $exercise),
                    'bi-alarm',
                    'deadline',
                    $key,
                );
                $sent++;
            }

            foreach ($quizzes as $quiz) {
                if ($quiz->attemptsFor($student)->exists()) {
                    continue;
                }

                $key = 'deadline:quiz:'.$quiz->id;

                if ($this->hasNotificationWithKey($student, $key)) {
                    continue;
                }

                $this->notify(
                    $student,
                    'Quiz à échéance proche',
                    $quiz->title.' — à terminer avant le '.$quiz->due_at->format('d/m/Y H:i'),
                    route('student.quizzes.show', $quiz),
                    'bi-alarm',
                    'deadline',
                    $key,
                );
                $sent++;
            }

            $tasks = TaskAssignment::where('user_id', $student->id)
                ->where('status', '!=', 'completed')
                ->whereHas('task', fn ($query) => $query->whereBetween('due_at', [now(), $limit]))
                ->with('task')
                ->get();

            foreach ($tasks as $assignment) {
                $key = 'deadline:task:'.$assignment->task_id;

                if ($this->hasNotificationWithKey($student, $key)) {
                    continue;
                }

                $this->notify(
                    $student,
                    'Tâche à échéance proche',
                    $assignment->task->title.' — avant le '.$assignment->task->due_at->format('d/m/Y H:i'),
                    route('student.tasks.show', $assignment->task),
                    'bi-alarm',
                    'deadline',
                    $key,
                );
                $sent++;
            }
        }

        return $sent;
    }
}
