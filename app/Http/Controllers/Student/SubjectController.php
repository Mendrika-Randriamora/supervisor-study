<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\TaskAssignment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user();

        return view('student.subjects.index', [
            'subjects' => Subject::query()
                ->whereNull('archived_at')
                ->with(['themes' => fn ($query) => $query->whereNull('archived_at')->withCount([
                    'exercises' => fn ($q) => $q->published(),
                    'quizzes' => fn ($q) => $q->published(),
                ])])
                ->withCount(['exercises' => fn ($query) => $query->published(), 'quizzes' => fn ($query) => $query->published()])
                ->orderBy('position')
                ->orderBy('name')
                ->get(),
            'student' => $student,
        ]);
    }

    public function show(Request $request, Subject $subject): View
    {
        abort_if($subject->archived_at !== null, 404);
        $student = $request->user();

        $exercises = Exercise::published()
            ->whereHas('theme', fn ($query) => $query->where('subject_id', $subject->id))
            ->with(['theme', 'submissions' => fn ($query) => $query->where('user_id', $student->id)->latest('version')])
            ->orderBy('due_at')
            ->get();

        $quizzes = Quiz::published()
            ->whereHas('theme', fn ($query) => $query->where('subject_id', $subject->id))
            ->withCount('questions')
            ->with(['theme', 'attempts' => fn ($query) => $query->where('user_id', $student->id)])
            ->orderBy('due_at')
            ->get();

        $tasks = TaskAssignment::query()
            ->where('user_id', $student->id)
            ->whereHas('task', fn ($query) => $query->where('subject_id', $subject->id))
            ->with('task.theme')
            ->get();

        return view('student.subjects.show', [
            'subject' => $subject->load('themes'),
            'exercises' => $exercises,
            'quizzes' => $quizzes,
            'tasks' => $tasks,
        ]);
    }
}
