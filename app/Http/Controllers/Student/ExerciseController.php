<?php

namespace App\Http\Controllers\Student;

use App\Enums\ExerciseType;
use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubmissionRequest;
use App\Models\Exercise;
use App\Models\ExerciseSubmission;
use App\Models\Subject;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExerciseController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user();
        $state = $request->string('state')->toString();
        $subjectId = $request->integer('subject') ?: null;

        $exercises = Exercise::published()
            ->with([
                'theme.subject',
                'submissions' => fn ($query) => $query->where('user_id', $student->id)->latest('version'),
            ])
            ->when($subjectId, fn ($query) => $query->whereHas('theme', fn ($q) => $q->where('subject_id', $subjectId)))
            ->orderByRaw('due_at IS NULL, due_at')
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        if ($state === 'todo') {
            $exercises->setCollection($exercises->getCollection()->filter(fn (Exercise $exercise) => $exercise->submissions->isEmpty())->values());
        } elseif ($state === 'done') {
            $exercises->setCollection($exercises->getCollection()->filter(fn (Exercise $exercise) => $exercise->submissions->isNotEmpty())->values());
        }

        return view('student.exercises.index', [
            'exercises' => $exercises,
            'subjects' => Subject::query()->whereNull('archived_at')->orderBy('name')->get(),
            'selectedSubject' => $subjectId,
            'state' => $state,
        ]);
    }

    public function show(Request $request, Exercise $exercise): View
    {
        $this->authorize('view', $exercise);
        $student = $request->user();
        $latestSubmission = $exercise->submissionFor($student);

        return view('student.exercises.show', [
            'exercise' => $exercise->load(['attachments', 'theme.subject']),
            'latestSubmission' => $latestSubmission,
            'history' => $exercise->submissions()->where('user_id', $student->id)->with('latestGrade')->latest('version')->get(),
            'canSubmit' => $latestSubmission === null || $latestSubmission->status === SubmissionStatus::RevisionRequested,
        ]);
    }

    public function submit(
        StoreSubmissionRequest $request,
        Exercise $exercise,
        ActivityLogger $activityLogger,
        NotificationService $notifications,
    ): RedirectResponse {
        $this->authorize('view', $exercise);
        $student = $request->user();
        $validated = $request->validated();

        if ($exercise->type === ExerciseType::Text && trim((string) ($validated['text_answer'] ?? '')) === '') {
            throw ValidationException::withMessages(['text_answer' => 'Votre réponse ne peut pas être vide.']);
        }

        if ($exercise->type === ExerciseType::Pdf && empty($request->file('files'))) {
            throw ValidationException::withMessages(['files' => 'Ajoutez au moins une photo de votre copie.']);
        }

        $submission = DB::transaction(function () use ($request, $exercise, $student, $validated): ExerciseSubmission {
            $existing = ExerciseSubmission::query()
                ->where('exercise_id', $exercise->id)
                ->where('user_id', $student->id)
                ->lockForUpdate()
                ->get();

            $latest = $existing->sortByDesc('version')->first();

            if ($latest && $latest->status !== SubmissionStatus::RevisionRequested) {
                throw ValidationException::withMessages([
                    'files' => 'Une soumission existe déjà pour cet exercice.',
                ]);
            }

            $version = ((int) $existing->max('version')) + 1;

            $submission = ExerciseSubmission::create([
                'exercise_id' => $exercise->id,
                'user_id' => $student->id,
                'version' => $version,
                'status' => SubmissionStatus::Submitted,
                'text_answer' => $exercise->type === ExerciseType::Text ? $validated['text_answer'] : null,
                'submitted_at' => now(),
                'is_late' => $exercise->due_at !== null && $exercise->due_at->isPast(),
            ]);

            foreach ($request->file('files', []) as $file) {
                $extension = strtolower($file->guessExtension() ?: $file->extension() ?: 'jpg');
                $filename = Str::uuid()->toString().'.'.$extension;
                $path = $file->storeAs("submissions/{$exercise->id}/{$student->id}", $filename, 'local');

                $submission->files()->create([
                    'path' => $path,
                    'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'size' => $file->getSize(),
                ]);
            }

            return $submission;
        });

        $activityLogger->log(
            $student,
            'exercise_submitted',
            'Exercice soumis : '.$exercise->title,
            $submission->is_late ? 'Soumission envoyée après l’échéance.' : null,
            $submission,
            ['version' => $submission->version, 'late' => $submission->is_late],
            $student,
        );

        foreach (User::supervisors()->active()->get() as $supervisor) {
            $notifications->submissionReceived(
                $supervisor,
                $student,
                $exercise->title,
                route('supervisor.submissions.show', $submission),
            );
        }

        return redirect()->route('student.submissions.show', $submission)
            ->with('success', 'Soumission envoyée. Votre superviseur va la corriger.');
    }
}
