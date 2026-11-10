<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ActivityEvent;
use App\Models\Exercise;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user();
        $subjectId = $request->integer('subject') ?: null;
        $type = $request->string('type')->toString();
        $from = $request->date('from');
        $to = $request->date('to');

        $events = ActivityEvent::query()
            ->where('student_id', $student->id)
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($subjectId, fn ($query) => $query->whereHasMorph(
                'subject',
                [Exercise::class, Quiz::class, Task::class],
                fn ($q) => $q->whereHas('theme', fn ($themeQuery) => $themeQuery->where('subject_id', $subjectId))
            ))
            ->when($from, fn ($query) => $query->where('occurred_at', '>=', $from->startOfDay()))
            ->when($to, fn ($query) => $query->where('occurred_at', '<=', $to->endOfDay()))
            ->latest('occurred_at')
            ->paginate(25)
            ->withQueryString();

        return view('student.history.index', [
            'events' => $events,
            'subjects' => Subject::orderBy('name')->get(),
            'types' => ActivityEvent::query()->where('student_id', $student->id)->distinct()->orderBy('type')->pluck('type'),
            'selectedSubject' => $subjectId,
            'selectedType' => $type,
            'from' => $from,
            'to' => $to,
        ]);
    }
}
