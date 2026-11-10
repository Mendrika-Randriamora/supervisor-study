<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\Exercise;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $month = $this->parseMonth($request->query('month'));
        $subjectId = $request->integer('subject') ?: null;
        $type = $request->string('type')->toString();
        $type = in_array($type, ['exercise', 'quiz', 'task'], true) ? $type : null;

        $events = $this->events($user, $month, $subjectId, $type);

        return view('calendar.index', [
            'month' => $month,
            'events' => $events,
            'eventsByDay' => $events->groupBy(fn (array $event) => $event['date']->format('Y-m-d')),
            'subjects' => Subject::query()->whereNull('archived_at')->orderBy('name')->get(),
            'selectedSubject' => $subjectId,
            'selectedType' => $type,
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'today' => today(),
        ]);
    }

    private function parseMonth(?string $value): Carbon
    {
        if ($value && preg_match('/^\d{4}-\d{2}$/', $value)) {
            return Carbon::createFromFormat('Y-m', $value)->startOfMonth();
        }

        return now()->startOfMonth();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function events(User $user, Carbon $month, ?int $subjectId, ?string $type): Collection
    {
        $start = $month->copy()->startOfMonth()->startOfDay();
        $end = $month->copy()->endOfMonth()->endOfDay();
        $events = collect();

        if (! $type || $type === 'exercise') {
            $exercises = Exercise::published()
                ->whereBetween('due_at', [$start, $end])
                ->when($subjectId, fn ($query) => $query->whereHas('theme', fn ($q) => $q->where('subject_id', $subjectId)))
                ->with('theme.subject')
                ->get();

            foreach ($exercises as $exercise) {
                $completed = $user->isStudent() && $exercise->submissionFor($user) !== null;

                $events->push([
                    'type' => 'exercise',
                    'label' => 'Exercice',
                    'title' => $exercise->title,
                    'subject' => $exercise->theme?->subject?->name,
                    'color' => $exercise->theme?->subject?->color ?? '#1e4d6b',
                    'date' => $exercise->due_at,
                    'completed' => $completed,
                    'url' => $user->isStudent()
                        ? route('student.exercises.show', $exercise)
                        : route('supervisor.exercises.show', $exercise),
                ]);
            }
        }

        if (! $type || $type === 'quiz') {
            $quizzes = Quiz::published()
                ->whereBetween('due_at', [$start, $end])
                ->when($subjectId, fn ($query) => $query->whereHas('theme', fn ($q) => $q->where('subject_id', $subjectId)))
                ->with('theme.subject')
                ->get();

            foreach ($quizzes as $quiz) {
                $completed = $user->isStudent() && $quiz->attemptsFor($user)->exists();

                $events->push([
                    'type' => 'quiz',
                    'label' => 'Quiz',
                    'title' => $quiz->title,
                    'subject' => $quiz->theme?->subject?->name,
                    'color' => $quiz->theme?->subject?->color ?? '#7a3e65',
                    'date' => $quiz->due_at,
                    'completed' => $completed,
                    'url' => $user->isStudent()
                        ? route('student.quizzes.show', $quiz)
                        : route('supervisor.quizzes.show', $quiz),
                ]);
            }
        }

        if (! $type || $type === 'task') {
            $tasks = Task::query()
                ->whereBetween('due_at', [$start, $end])
                ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
                ->when($user->isStudent(), fn ($query) => $query->whereHas(
                    'assignments',
                    fn ($q) => $q->where('user_id', $user->id)
                ))
                ->with('subject')
                ->get();

            foreach ($tasks as $task) {
                $assignment = $user->isStudent() ? $task->assignmentFor($user) : null;

                $events->push([
                    'type' => 'task',
                    'label' => 'Tâche',
                    'title' => $task->title,
                    'subject' => $task->subject?->name,
                    'color' => $task->subject?->color ?? '#7a5c1e',
                    'date' => $task->due_at,
                    'completed' => $assignment?->status === TaskStatus::Completed,
                    'url' => $user->isStudent()
                        ? route('student.tasks.show', $task)
                        : route('supervisor.tasks.show', $task),
                ]);
            }
        }

        if ($user->isStudent() && (! $type || $type === 'exercise')) {
            $submissions = $user->submissions()
                ->whereBetween('submitted_at', [$start, $end])
                ->with('exercise.theme.subject')
                ->get();

            foreach ($submissions as $submission) {
                $events->push([
                    'type' => 'completed',
                    'label' => 'Soumission',
                    'title' => $submission->exercise?->title ?? 'Exercice',
                    'subject' => $submission->exercise?->theme?->subject?->name,
                    'color' => '#2f6b4f',
                    'date' => $submission->submitted_at,
                    'completed' => true,
                    'url' => route('student.submissions.show', $submission),
                ]);
            }

        }

        if ($user->isStudent() && (! $type || $type === 'quiz')) {
            $attempts = $user->quizAttempts()
                ->whereBetween('submitted_at', [$start, $end])
                ->with('quiz.theme.subject')
                ->get();

            foreach ($attempts as $attempt) {
                $events->push([
                    'type' => 'completed',
                    'label' => 'Quiz réalisé',
                    'title' => $attempt->quiz?->title ?? 'Quiz',
                    'subject' => $attempt->quiz?->theme?->subject?->name,
                    'color' => '#2f6b4f',
                    'date' => $attempt->submitted_at,
                    'completed' => true,
                    'url' => route('student.results.show', $attempt->quiz_id),
                ]);
            }
        }

        return $events->sortBy(fn (array $event) => $event['date']->timestamp)->values();
    }
}
