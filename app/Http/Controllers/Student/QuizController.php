<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuizAttemptRequest;
use App\Models\Quiz;
use App\Models\Subject;
use App\Services\ActivityLogger;
use App\Services\QuizGradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user();
        $subjectId = $request->integer('subject') ?: null;

        $quizzes = Quiz::published()
            ->withCount('questions')
            ->with([
                'theme.subject',
                'attempts' => fn ($query) => $query->where('user_id', $student->id)->orderByDesc('submitted_at'),
            ])
            ->when($subjectId, fn ($query) => $query->whereHas('theme', fn ($q) => $q->where('subject_id', $subjectId)))
            ->orderByRaw('due_at IS NULL, due_at')
            ->paginate(12)
            ->withQueryString();

        return view('student.quizzes.index', [
            'quizzes' => $quizzes,
            'subjects' => Subject::query()->whereNull('archived_at')->orderBy('name')->get(),
            'selectedSubject' => $subjectId,
        ]);
    }

    public function show(Quiz $quiz): View
    {
        $this->authorize('view', $quiz);
        $student = request()->user();

        return view('student.quizzes.show', [
            'quiz' => $quiz->load(['theme.subject'])->loadCount('questions'),
            'attempts' => $quiz->attemptsFor($student)->with([])->latest('submitted_at')->get(),
            'bestAttempt' => $quiz->bestAttemptFor($student),
            'latestAttempt' => $quiz->latestAttemptFor($student),
        ]);
    }

    public function start(Quiz $quiz): RedirectResponse
    {
        $this->authorize('attempt', $quiz);

        if ($quiz->isOverdue()) {
            return back()->withErrors(['quiz' => 'La date limite de ce quiz est dépassée.']);
        }

        session(["quiz_started_at.{$quiz->id}" => now()->timestamp]);

        return redirect()->route('student.quizzes.take', $quiz);
    }

    public function take(Quiz $quiz): View|RedirectResponse
    {
        $this->authorize('attempt', $quiz);

        if ($quiz->isOverdue()) {
            return redirect()->route('student.quizzes.show', $quiz)
                ->withErrors(['quiz' => 'La date limite de ce quiz est dépassée.']);
        }

        if (! session()->has("quiz_started_at.{$quiz->id}")) {
            session(["quiz_started_at.{$quiz->id}" => now()->timestamp]);
        }

        $quiz->load(['theme.subject', 'questions' => fn ($query) => $query->orderBy('position')->orderBy('id'), 'questions.options' => fn ($query) => $query->select('id', 'quiz_question_id', 'option_text', 'position')->orderBy('position')]);

        return view('student.quizzes.take', [
            'quiz' => $quiz,
            'startedAt' => session("quiz_started_at.{$quiz->id}"),
        ]);
    }

    public function submit(
        StoreQuizAttemptRequest $request,
        Quiz $quiz,
        QuizGradingService $grader,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        $this->authorize('attempt', $quiz);

        if ($quiz->isOverdue()) {
            return back()->withErrors(['quiz' => 'La date limite de ce quiz est dépassée.']);
        }

        $startedAt = session("quiz_started_at.{$quiz->id}");
        $duration = $startedAt ? max(0, now()->timestamp - (int) $startedAt) : null;

        $attempt = $grader->grade($quiz, $request->user(), $request->validated('answers'), $duration);
        session()->forget("quiz_started_at.{$quiz->id}");

        $activityLogger->log(
            $request->user(),
            'quiz_submitted',
            'Quiz réalisé : '.$quiz->title,
            'Tentative n°'.$attempt->attempt_number.' — '.$attempt->score.' / '.$attempt->max_points,
            $quiz,
            ['attempt_id' => $attempt->id, 'attempt_number' => $attempt->attempt_number, 'score' => (float) $attempt->score],
            $request->user(),
        );

        return redirect()->route('student.quizzes.result', ['quiz' => $quiz, 'attempt' => $attempt])
            ->with('success', 'Quiz soumis. Votre résultat est disponible ci-dessous.');
    }

    public function result(Quiz $quiz, int $attempt): View
    {
        $student = request()->user();
        $attempt = $quiz->attempts()->where('user_id', $student->id)->whereKey($attempt)->firstOrFail();

        $attempt->load([
            'answers.question.options',
            'answers.selectedOptions',
            'quiz.theme.subject',
        ]);

        return view('student.quizzes.result', [
            'quiz' => $quiz,
            'attempt' => $attempt,
            'attempts' => $quiz->attemptsFor($student)->latest('submitted_at')->get(),
        ]);
    }
}
