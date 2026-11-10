<?php

namespace App\Services;

use App\Enums\SubmissionStatus;
use App\Enums\TaskStatus;
use App\Models\ActivityEvent;
use App\Models\Exercise;
use App\Models\ExerciseSubmission;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\TaskAssignment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    /**
     * Full academic overview for one student.
     *
     * @return array<string, mixed>
     */
    public function studentOverview(User $student): array
    {
        $publishedExercises = Exercise::published()->with('theme.subject')->orderBy('due_at')->get();
        $publishedQuizzes = Quiz::published()->with('theme.subject')->orderBy('due_at')->get();

        $submissions = ExerciseSubmission::query()
            ->where('user_id', $student->id)
            ->with(['exercise.theme.subject', 'latestGrade'])
            ->get();

        $latestSubmissions = $submissions
            ->sortBy('version')
            ->groupBy('exercise_id')
            ->map(fn (Collection $group) => $group->last());

        $gradedSubmissions = $submissions
            ->filter(fn (ExerciseSubmission $submission) => $submission->latestGrade !== null)
            ->sortBy('version')
            ->groupBy('exercise_id')
            ->map(fn (Collection $group) => $group->last());

        $attempts = QuizAttempt::query()
            ->where('user_id', $student->id)
            ->with('quiz.theme.subject')
            ->get();

        $bestAttempts = $attempts
            ->groupBy('quiz_id')
            ->map(fn (Collection $group) => $group->sortByDesc('score')->first());

        $taskAssignments = TaskAssignment::query()
            ->where('user_id', $student->id)
            ->with('task.subject')
            ->get();

        $activities = collect();

        foreach ($gradedSubmissions as $submission) {
            $grade = $submission->latestGrade;
            $activities->push([
                'kind' => 'exercise',
                'title' => $submission->exercise?->title ?? 'Exercice',
                'subject' => $submission->exercise?->theme?->subject?->name,
                'subject_id' => $submission->exercise?->theme?->subject?->id,
                'theme' => $submission->exercise?->theme?->name,
                'score' => (float) $grade->score,
                'max_points' => (float) $grade->max_points,
                'converted' => $grade->convertedTo20(),
                'date' => $grade->graded_at,
                'url' => route('student.submissions.show', $submission),
            ]);
        }

        foreach ($bestAttempts as $attempt) {
            $activities->push([
                'kind' => 'quiz',
                'title' => $attempt->quiz?->title ?? 'Quiz',
                'subject' => $attempt->quiz?->theme?->subject?->name,
                'subject_id' => $attempt->quiz?->theme?->subject?->id,
                'theme' => $attempt->quiz?->theme?->name,
                'score' => (float) $attempt->score,
                'max_points' => (float) $attempt->max_points,
                'converted' => $attempt->convertedTo20(),
                'date' => $attempt->submitted_at,
                'url' => route('student.results.show', $attempt->quiz_id),
            ]);
        }

        $activities = $activities->sortBy(fn (array $activity) => $activity['date']->timestamp)->values();
        $average = $activities->isNotEmpty() ? round($activities->avg('converted'), 2) : null;

        $taskTotal = $taskAssignments->count();
        $taskCompleted = $taskAssignments->filter(fn (TaskAssignment $assignment) => $assignment->status === TaskStatus::Completed)->count();
        $taskOverdue = $taskAssignments->filter(
            fn (TaskAssignment $assignment) => $assignment->status !== TaskStatus::Completed
                && $assignment->task?->due_at?->isPast()
        )->count();

        $submittedExerciseIds = $latestSubmissions->keys();
        $attemptedQuizIds = $attempts->pluck('quiz_id')->unique();

        $subjectStats = $this->subjectStats(
            $publishedExercises,
            $publishedQuizzes,
            $latestSubmissions,
            $attemptedQuizIds,
            $taskAssignments,
            $activities,
        );

        return [
            'average' => $average,
            'graded_count' => $activities->count(),
            'activities' => $activities,
            'exercises' => [
                'total' => $publishedExercises->count(),
                'submitted' => $publishedExercises->whereIn('id', $submittedExerciseIds)->count(),
                'graded' => $gradedSubmissions->count(),
                'missing' => $publishedExercises->whereNotIn('id', $submittedExerciseIds)->count(),
            ],
            'quizzes' => [
                'total' => $publishedQuizzes->count(),
                'attempted' => $attemptedQuizIds->count(),
                'attempts' => $attempts->count(),
                'missing' => $publishedQuizzes->whereNotIn('id', $attemptedQuizIds)->count(),
            ],
            'tasks' => [
                'total' => $taskTotal,
                'completed' => $taskCompleted,
                'overdue' => $taskOverdue,
                'rate' => $taskTotal > 0 ? round(($taskCompleted / $taskTotal) * 100, 1) : 0.0,
            ],
            'subjects' => $subjectStats,
            'timeline' => $activities->map(fn (array $activity, int $index) => [
                'date' => $activity['date']->format('d/m/Y'),
                'converted' => $activity['converted'],
                'running_average' => round($activities->take($index + 1)->avg('converted'), 2),
            ])->values(),
            'task_chart' => $this->monthlyTaskChart($student),
            'recent_grades' => $activities->sortByDesc(fn (array $activity) => $activity['date']->timestamp)->take(6)->values(),
            'upcoming' => $this->upcomingFor($student, $publishedExercises, $publishedQuizzes, $taskAssignments),
        ];
    }

    /**
     * @param  Collection<int, Exercise>  $exercises
     * @param  Collection<int, Quiz>  $quizzes
     * @param  Collection<int, ExerciseSubmission>  $latestSubmissions
     * @param  Collection<int, int>  $attemptedQuizIds
     * @param  Collection<int, TaskAssignment>  $taskAssignments
     * @param  Collection<int, array<string, mixed>>  $activities
     * @return Collection<int, array<string, mixed>>
     */
    private function subjectStats(
        Collection $exercises,
        Collection $quizzes,
        Collection $latestSubmissions,
        Collection $attemptedQuizIds,
        Collection $taskAssignments,
        Collection $activities,
    ): Collection {
        return Subject::query()
            ->whereNull('archived_at')
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->map(function (Subject $subject) use ($exercises, $quizzes, $latestSubmissions, $attemptedQuizIds, $taskAssignments, $activities): array {
                $subjectExercises = $exercises->filter(fn (Exercise $exercise) => $exercise->theme?->subject_id === $subject->id);
                $subjectQuizzes = $quizzes->filter(fn (Quiz $quiz) => $quiz->theme?->subject_id === $subject->id);
                $submitted = $subjectExercises->filter(fn (Exercise $exercise) => $latestSubmissions->has($exercise->id))->count();
                $attempted = $subjectQuizzes->filter(fn (Quiz $quiz) => $attemptedQuizIds->contains($quiz->id))->count();
                $subjectTasks = $taskAssignments->filter(fn (TaskAssignment $assignment) => $assignment->task?->subject_id === $subject->id);
                $completedTasks = $subjectTasks->filter(fn (TaskAssignment $assignment) => $assignment->status === TaskStatus::Completed)->count();
                $subjectActivities = $activities->filter(fn (array $activity) => $activity['subject_id'] === $subject->id);
                $passed = $subjectActivities->where('converted', '>=', 10)->count();

                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'color' => $subject->color,
                    'average' => $subjectActivities->isNotEmpty() ? round($subjectActivities->avg('converted'), 2) : null,
                    'graded_count' => $subjectActivities->count(),
                    'completed' => $submitted + $attempted,
                    'remaining' => ($subjectExercises->count() - $submitted) + ($subjectQuizzes->count() - $attempted),
                    'exercises_total' => $subjectExercises->count(),
                    'exercises_submitted' => $submitted,
                    'quizzes_total' => $subjectQuizzes->count(),
                    'quizzes_attempted' => $attempted,
                    'tasks_total' => $subjectTasks->count(),
                    'tasks_completed' => $completedTasks,
                    'success_rate' => $subjectActivities->isNotEmpty() ? round(($passed / $subjectActivities->count()) * 100, 1) : null,
                ];
            })
            ->filter(fn (array $subject) => $subject['exercises_total'] > 0
                || $subject['quizzes_total'] > 0
                || $subject['tasks_total'] > 0
                || $subject['graded_count'] > 0)
            ->values();
    }

    /**
     * @param  Collection<int, Exercise>  $exercises
     * @param  Collection<int, Quiz>  $quizzes
     * @param  Collection<int, TaskAssignment>  $taskAssignments
     * @return Collection<int, array<string, mixed>>
     */
    private function upcomingFor(User $student, Collection $exercises, Collection $quizzes, Collection $taskAssignments): Collection
    {
        $items = collect();

        foreach ($exercises as $exercise) {
            if ($exercise->due_at && $exercise->due_at->isFuture() && ! $exercise->submissionFor($student)) {
                $items->push([
                    'type' => 'Exercice',
                    'icon' => 'bi-journal-check',
                    'title' => $exercise->title,
                    'due_at' => $exercise->due_at,
                    'url' => route('student.exercises.show', $exercise),
                ]);
            }
        }

        foreach ($quizzes as $quiz) {
            if ($quiz->due_at && $quiz->due_at->isFuture() && ! $quiz->attemptsFor($student)->exists()) {
                $items->push([
                    'type' => 'Quiz',
                    'icon' => 'bi-patch-question',
                    'title' => $quiz->title,
                    'due_at' => $quiz->due_at,
                    'url' => route('student.quizzes.show', $quiz),
                ]);
            }
        }

        foreach ($taskAssignments as $assignment) {
            if ($assignment->status !== TaskStatus::Completed && $assignment->task?->due_at?->isFuture()) {
                $items->push([
                    'type' => 'Tâche',
                    'icon' => 'bi-list-task',
                    'title' => $assignment->task->title,
                    'due_at' => $assignment->task->due_at,
                    'url' => route('student.tasks.show', $assignment->task),
                ]);
            }
        }

        return $items->sortBy(fn (array $item) => $item['due_at']->timestamp)->take(8)->values();
    }

    /**
     * @return array{labels: array<int, string>, completed: array<int, int>, assigned: array<int, int>}
     */
    private function monthlyTaskChart(User $student): array
    {
        $labels = [];
        $completed = [];
        $assigned = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = ucfirst($month->translatedFormat('M Y'));
            $assigned[] = TaskAssignment::query()
                ->where('user_id', $student->id)
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
            $completed[] = TaskAssignment::query()
                ->where('user_id', $student->id)
                ->whereNotNull('completed_at')
                ->whereYear('completed_at', $month->year)
                ->whereMonth('completed_at', $month->month)
                ->count();
        }

        return ['labels' => $labels, 'completed' => $completed, 'assigned' => $assigned];
    }

    /**
     * Global indicators for the supervisor dashboard.
     *
     * @return array<string, mixed>
     */
    public function supervisorOverview(): array
    {
        $students = User::students()->active()->get();
        $totalAssignments = TaskAssignment::count();
        $completedAssignments = TaskAssignment::where('status', TaskStatus::Completed)->count();
        $overdueAssignments = TaskAssignment::query()
            ->where('status', '!=', TaskStatus::Completed)
            ->whereHas('task', fn ($query) => $query->where('due_at', '<', now()))
            ->count();

        return [
            'students_count' => $students->count(),
            'published_exercises' => Exercise::published()->count(),
            'published_quizzes' => Quiz::published()->count(),
            'in_progress_tasks' => TaskAssignment::where('status', '!=', TaskStatus::Completed)->count(),
            'pending_submissions' => ExerciseSubmission::where('status', SubmissionStatus::Submitted)->count(),
            'overdue_tasks' => $overdueAssignments,
            'task_completion_rate' => $totalAssignments > 0 ? round(($completedAssignments / $totalAssignments) * 100, 1) : 0.0,
            'pending_submissions_list' => ExerciseSubmission::query()
                ->where('status', SubmissionStatus::Submitted)
                ->with(['exercise', 'student'])
                ->orderBy('submitted_at')
                ->limit(6)
                ->get(),
            'recent_events' => ActivityEvent::with(['student', 'actor'])
                ->latest('occurred_at')
                ->limit(8)
                ->get(),
            'student_summaries' => $students->take(8)->map(function (User $student): array {
                $overview = $this->studentOverview($student);

                return [
                    'student' => $student,
                    'average' => $overview['average'],
                    'graded_count' => $overview['graded_count'],
                    'task_rate' => $overview['tasks']['rate'],
                    'missing_exercises' => $overview['exercises']['missing'],
                ];
            }),
            'global_task_chart' => $this->globalTaskChart(),
        ];
    }

    /**
     * @return array{labels: array<int, string>, submitted: array<int, int>, graded: array<int, int>}
     */
    private function globalTaskChart(): array
    {
        $labels = [];
        $submitted = [];
        $graded = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = ucfirst($month->translatedFormat('M Y'));
            $submitted[] = ExerciseSubmission::query()
                ->whereYear('submitted_at', $month->year)
                ->whereMonth('submitted_at', $month->month)
                ->count();
            $graded[] = DB::table('exercise_submissions')
                ->whereNotNull('graded_at')
                ->whereYear('graded_at', $month->year)
                ->whereMonth('graded_at', $month->month)
                ->count();
        }

        return ['labels' => $labels, 'submitted' => $submitted, 'graded' => $graded];
    }

    /**
     * Average converted to /20 from an arbitrary collection of grades/attempts.
     *
     * @param  iterable<int, ExerciseSubmission|QuizAttempt>  $items
     */
    public function averageFor(iterable $items): ?float
    {
        $values = collect($items)->map(fn ($item) => $item->convertedTo20());

        return $values->isNotEmpty() ? round($values->avg(), 2) : null;
    }
}
