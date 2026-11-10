<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Models\Exercise;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\Task;
use App\Models\Theme;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $subjectId = $request->integer('subject') ?: null;
        $studentId = $request->integer('student') ?: null;

        $tasks = Task::query()
            ->with(['subject', 'exercise', 'quiz'])
            ->withCount([
                'assignments',
                'assignments as completed_assignments_count' => fn ($query) => $query->where('status', TaskStatus::Completed),
            ])
            ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
            ->when($studentId, fn ($query) => $query->whereHas('assignments', fn ($q) => $q->where('user_id', $studentId)))
            ->when($status === 'overdue', fn ($query) => $query->where('due_at', '<', now())->whereHas('assignments', fn ($q) => $q->where('status', '!=', TaskStatus::Completed)))
            ->when($status === 'open', fn ($query) => $query->whereHas('assignments', fn ($q) => $q->where('status', '!=', TaskStatus::Completed)))
            ->when($status === 'completed', fn ($query) => $query->whereDoesntHave('assignments', fn ($q) => $q->where('status', '!=', TaskStatus::Completed)))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('supervisor.tasks.index', [
            'tasks' => $tasks,
            'subjects' => Subject::orderBy('name')->get(),
            'students' => User::students()->active()->orderBy('name')->get(),
            'selectedSubject' => $subjectId,
            'selectedStudent' => $studentId,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('supervisor.tasks.create', $this->formData());
    }

    public function store(TaskRequest $request, ActivityLogger $activityLogger, NotificationService $notifications): RedirectResponse
    {
        $data = $request->validated();
        $studentIds = $data['student_ids'];
        unset($data['student_ids']);
        $data['created_by'] = $request->user()->id;

        $task = DB::transaction(function () use ($data, $studentIds): Task {
            $task = Task::create($data);

            foreach ($studentIds as $studentId) {
                $task->assignments()->create([
                    'user_id' => $studentId,
                    'status' => TaskStatus::Todo,
                ]);
            }

            return $task;
        });

        $this->announceAssignments($task, $studentIds, $activityLogger, $notifications);

        return redirect()->route('supervisor.tasks.show', $task)->with('success', 'Tâche créée et attribuée.');
    }

    public function show(Task $task): View
    {
        return view('supervisor.tasks.show', [
            'task' => $task->load(['subject', 'theme', 'exercise', 'quiz', 'assignments.student']),
        ]);
    }

    public function edit(Task $task): View
    {
        return view('supervisor.tasks.edit', [
            ...$this->formData(),
            'task' => $task->load('assignments'),
        ]);
    }

    public function update(TaskRequest $request, Task $task, ActivityLogger $activityLogger, NotificationService $notifications): RedirectResponse
    {
        $data = $request->validated();
        $studentIds = collect($data['student_ids'])->map(fn ($id) => (int) $id);
        unset($data['student_ids']);

        $newlyAssigned = DB::transaction(function () use ($task, $data, $studentIds): array {
            $task->update($data);
            $existing = $task->assignments()->get()->keyBy('user_id');

            foreach ($studentIds as $studentId) {
                if (! $existing->has($studentId)) {
                    $task->assignments()->create([
                        'user_id' => $studentId,
                        'status' => TaskStatus::Todo,
                    ]);
                }
            }

            foreach ($existing as $studentId => $assignment) {
                if (! $studentIds->contains($studentId) && $assignment->status !== TaskStatus::Completed) {
                    $assignment->delete();
                }
            }

            return $studentIds->diff($existing->keys())->values()->all();
        });

        $this->announceAssignments($task->fresh(), $newlyAssigned, $activityLogger, $notifications);

        return redirect()->route('supervisor.tasks.show', $task)->with('success', 'Tâche mise à jour.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return redirect()->route('supervisor.tasks.index')
            ->with('success', 'Tâche supprimée. Les données restent conservées en base.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'subjects' => Subject::query()->whereNull('archived_at')->orderBy('name')->get(),
            'themes' => Theme::query()->whereNull('archived_at')->with('subject')->orderBy('name')->get(),
            'exercises' => Exercise::query()->with('theme.subject')->orderBy('title')->get(),
            'quizzes' => Quiz::query()->with('theme.subject')->orderBy('title')->get(),
            'students' => User::students()->active()->orderBy('name')->get(),
            'priorities' => TaskPriority::cases(),
        ];
    }

    /**
     * @param  array<int, int|string>  $studentIds
     */
    private function announceAssignments(
        Task $task,
        array $studentIds,
        ActivityLogger $activityLogger,
        NotificationService $notifications,
    ): void {
        $students = User::students()->active()->whereIn('id', $studentIds)->get();

        foreach ($students as $student) {
            $notifications->taskAssigned($task, $student);
            $activityLogger->log(
                $student,
                'task_assigned',
                'Tâche attribuée : '.$task->title,
                $task->due_at ? 'À réaliser avant le '.$task->due_at->format('d/m/Y H:i') : null,
                $task,
            );
        }
    }
}
