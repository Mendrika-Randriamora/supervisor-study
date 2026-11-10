<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\ActivityEvent;
use App\Models\Exercise;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\Task;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(Request $request): View
    {
        $studentId = $request->integer('student') ?: null;
        $subjectId = $request->integer('subject') ?: null;
        $themeId = $request->integer('theme') ?: null;
        $type = $request->string('type')->toString();
        $from = $request->date('from');
        $to = $request->date('to');

        $events = ActivityEvent::query()
            ->with(['student', 'actor'])
            ->when($studentId, fn ($query) => $query->where('student_id', $studentId))
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($subjectId, fn ($query) => $query->whereHasMorph(
                'subject',
                [Exercise::class, Quiz::class, Task::class],
                fn ($q) => $q->whereHas('theme', fn ($themeQuery) => $themeQuery->where('subject_id', $subjectId))
            ))
            ->when($themeId, fn ($query) => $query->where(function ($q) use ($themeId) {
                $q->whereHasMorph('subject', [Exercise::class], fn ($sub) => $sub->where('theme_id', $themeId))
                    ->orWhereHasMorph('subject', [Quiz::class], fn ($sub) => $sub->where('theme_id', $themeId))
                    ->orWhereHasMorph('subject', [Task::class], fn ($sub) => $sub->where(function ($task) use ($themeId) {
                        $task->where('theme_id', $themeId)
                            ->orWhereHas('exercise', fn ($exercise) => $exercise->where('theme_id', $themeId))
                            ->orWhereHas('quiz', fn ($quiz) => $quiz->where('theme_id', $themeId));
                    }));
            }))
            ->when($from, fn ($query) => $query->where('occurred_at', '>=', $from->startOfDay()))
            ->when($to, fn ($query) => $query->where('occurred_at', '<=', $to->endOfDay()))
            ->latest('occurred_at')
            ->paginate(25)
            ->withQueryString();

        return view('supervisor.history.index', [
            'events' => $events,
            'students' => User::students()->orderBy('name')->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'themes' => Theme::query()->with('subject')->orderBy('name')->get(),
            'types' => ActivityEvent::query()->distinct()->orderBy('type')->pluck('type'),
            'selectedStudent' => $studentId,
            'selectedSubject' => $subjectId,
            'selectedTheme' => $themeId,
            'selectedType' => $type,
            'from' => $from,
            'to' => $to,
        ]);
    }
}
