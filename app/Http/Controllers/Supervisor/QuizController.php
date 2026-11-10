<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\QuizStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuizRequest;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\Theme;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $subjectId = $request->integer('subject') ?: null;

        $quizzes = Quiz::query()
            ->with('theme.subject')
            ->withCount(['questions', 'attempts'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($subjectId, fn ($query) => $query->whereHas('theme', fn ($q) => $q->where('subject_id', $subjectId)))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('supervisor.quizzes.index', [
            'quizzes' => $quizzes,
            'subjects' => Subject::orderBy('name')->get(),
            'statuses' => QuizStatus::cases(),
            'status' => $status,
            'selectedSubject' => $subjectId,
        ]);
    }

    public function create(): View
    {
        return view('supervisor.quizzes.create', $this->formData());
    }

    public function store(QuizRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] = QuizStatus::Draft;
        $data['published_at'] = null;
        $data['created_by'] = $request->user()->id;

        $quiz = Quiz::create($data);

        return redirect()->route('supervisor.quizzes.show', $quiz)
            ->with('success', 'Quiz créé en brouillon. Ajoutez maintenant les questions avant de le publier.');
    }

    public function show(Quiz $quiz): View
    {
        $quiz->load(['theme.subject', 'questions.options']);

        $attempts = $quiz->attempts()
            ->with('student')
            ->orderByDesc('submitted_at')
            ->get()
            ->groupBy('user_id');

        return view('supervisor.quizzes.show', [
            'quiz' => $quiz,
            'attemptsByStudent' => $attempts,
            'attemptCount' => $quiz->attempts()->count(),
        ]);
    }

    public function edit(Quiz $quiz): View
    {
        return view('supervisor.quizzes.edit', [
            ...$this->formData(),
            'quiz' => $quiz,
        ]);
    }

    public function update(
        QuizRequest $request,
        Quiz $quiz,
        ActivityLogger $activityLogger,
        NotificationService $notifications,
    ): RedirectResponse {
        $data = $request->validated();
        $wasPublished = $quiz->status === QuizStatus::Published;
        $status = QuizStatus::from($data['status']);

        if ($status === QuizStatus::Published && ! $quiz->questions()->exists()) {
            throw ValidationException::withMessages(['status' => 'Ajoutez au moins une question avant de publier le quiz.']);
        }

        if ($status === QuizStatus::Published) {
            $data['published_at'] = $data['published_at'] ?? $quiz->published_at ?? now();
        } elseif ($status === QuizStatus::Draft) {
            $data['published_at'] = null;
        } else {
            $data['published_at'] = $data['published_at'] ?? $quiz->published_at;
        }

        $quiz->update($data);

        if (! $wasPublished && $status === QuizStatus::Published) {
            $this->announcePublication($quiz, $activityLogger, $notifications);
        }

        return redirect()->route('supervisor.quizzes.show', $quiz)->with('success', 'Quiz mis à jour.');
    }

    public function publish(Quiz $quiz, ActivityLogger $activityLogger, NotificationService $notifications): RedirectResponse
    {
        if (! $quiz->questions()->exists()) {
            return back()->withErrors(['status' => 'Ajoutez au moins une question avant de publier le quiz.']);
        }

        $wasPublished = $quiz->status === QuizStatus::Published;
        $quiz->forceFill([
            'status' => QuizStatus::Published,
            'published_at' => $quiz->published_at ?? now(),
        ])->save();

        if (! $wasPublished) {
            $this->announcePublication($quiz, $activityLogger, $notifications);
        }

        return back()->with('success', 'Quiz publié. Les étudiants ont été notifiés.');
    }

    public function archive(Quiz $quiz): RedirectResponse
    {
        $quiz->forceFill(['status' => QuizStatus::Archived])->save();

        return back()->with('success', 'Quiz archivé.');
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        if ($quiz->attempts()->exists()) {
            return back()->withErrors(['quiz' => 'Des tentatives existent. Archivez le quiz pour conserver les résultats.']);
        }

        $quiz->delete();

        return redirect()->route('supervisor.quizzes.index')->with('success', 'Quiz supprimé.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'subjects' => Subject::query()->whereNull('archived_at')->with(['themes' => fn ($query) => $query->whereNull('archived_at')])->orderBy('name')->get(),
            'themes' => Theme::query()->whereNull('archived_at')->with('subject')->orderBy('name')->get(),
            'statuses' => QuizStatus::cases(),
        ];
    }

    private function announcePublication(Quiz $quiz, ActivityLogger $activityLogger, NotificationService $notifications): void
    {
        $quiz->loadMissing('theme.subject');
        $notifications->quizPublished($quiz);

        foreach (User::students()->active()->get() as $student) {
            $activityLogger->log(
                $student,
                'quiz_published',
                'Quiz disponible : '.$quiz->title,
                'Nouveau quiz en '.$quiz->theme?->subject?->name.'.',
                $quiz,
            );
        }
    }
}
