<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Services\StatisticsService;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function index(StatisticsService $statistics): View
    {
        $student = request()->user();

        return view('student.results.index', [
            'overview' => $statistics->studentOverview($student),
        ]);
    }

    public function show(Quiz $quiz): View
    {
        $student = request()->user();
        abort_unless($quiz->attemptsFor($student)->exists(), 403);

        return view('student.results.show', [
            'quiz' => $quiz->load(['theme.subject']),
            'attempts' => $quiz->attemptsFor($student)
                ->with(['answers.question.options', 'answers.selectedOptions'])
                ->latest('submitted_at')
                ->get(),
        ]);
    }
}
