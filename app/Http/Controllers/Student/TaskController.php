<?php

namespace App\Http\Controllers\Student;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\Subject;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user();
        $status = $request->string('status')->toString();
        $subjectId = $request->integer('subject') ?: null;

        $assignments = TaskAssignment::query()
            ->where('user_id', $student->id)
            ->with(['task.subject', 'task.theme', 'task.exercise', 'task.quiz'])
            ->when($subjectId, fn ($query) => $query->whereHas('task', fn ($q) => $q->where('subject_id', $subjectId)))
            ->when($status === 'completed', fn ($query) => $query->where('status', TaskStatus::Completed))
            ->when($status === 'open', fn ($query) => $query->where('status', '!=', TaskStatus::Completed))
            ->when($status === 'overdue', fn ($query) => $query->where('status', '!=', TaskStatus::Completed)->whereHas('task', fn ($q) => $q->where('due_at', '<', now())))
            ->join('tasks', 'tasks.id', '=', 'task_assignments.task_id')
            ->orderByRaw('tasks.due_at IS NULL, tasks.due_at')
            ->select('task_assignments.*')
            ->paginate(15)
            ->withQueryString();

        return view('student.tasks.index', [
            'assignments' => $assignments,
            'subjects' => Subject::query()->whereNull('archived_at')->orderBy('name')->get(),
            'status' => $status,
            'selectedSubject' => $subjectId,
        ]);
    }

    public function show(Task $task): View
    {
        $this->authorize('view', $task);
        $assignment = $task->assignmentFor(request()->user());
        abort_unless($assignment !== null, 404);

        return view('student.tasks.show', [
            'task' => $task->load(['subject', 'theme', 'exercise', 'quiz']),
            'assignment' => $assignment,
        ]);
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task, ActivityLogger $activityLogger): RedirectResponse
    {
        $this->authorize('update', $task);
        $assignment = $task->assignmentFor($request->user());
        abort_unless($assignment !== null, 404);

        $status = TaskStatus::from($request->validated('status'));
        $wasCompleted = $assignment->status === TaskStatus::Completed;

        $assignment->forceFill([
            'status' => $status,
            'completed_at' => $status === TaskStatus::Completed ? ($assignment->completed_at ?? now()) : null,
        ])->save();

        if ($status === TaskStatus::Completed && ! $wasCompleted) {
            $activityLogger->log(
                $request->user(),
                'task_completed',
                'Tâche terminée : '.$task->title,
                null,
                $task,
                [],
                $request->user(),
            );
        } elseif ($status !== TaskStatus::Completed && $wasCompleted) {
            $activityLogger->log(
                $request->user(),
                'task_reopened',
                'Tâche rouverte : '.$task->title,
                null,
                $task,
                [],
                $request->user(),
            );
        }

        return back()->with('success', 'Statut de la tâche mis à jour.');
    }
}
