<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\ExerciseStatus;
use App\Enums\ExerciseType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExerciseRequest;
use App\Models\Exercise;
use App\Models\ExerciseAttachment;
use App\Models\Subject;
use App\Models\Theme;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExerciseController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $subjectId = $request->integer('subject') ?: null;
        $search = $request->string('q')->toString();

        $exercises = Exercise::query()
            ->with(['theme.subject'])
            ->withCount(['attachments', 'submissions'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($subjectId, fn ($query) => $query->whereHas('theme', fn ($q) => $q->where('subject_id', $subjectId)))
            ->when($search, fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('supervisor.exercises.index', [
            'exercises' => $exercises,
            'subjects' => Subject::orderBy('name')->get(),
            'statuses' => ExerciseStatus::cases(),
            'status' => $status,
            'selectedSubject' => $subjectId,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('supervisor.exercises.create', $this->formData());
    }

    public function store(ExerciseRequest $request, ActivityLogger $activityLogger, NotificationService $notifications): RedirectResponse
    {
        $data = $request->validated();
        $document = $request->file('document');
        $status = ExerciseStatus::from($data['status']);

        if ($data['type'] === ExerciseType::Pdf->value && $status === ExerciseStatus::Published && ! $document) {
            throw ValidationException::withMessages(['document' => 'Un exercice PDF publié doit contenir un document PDF.']);
        }

        $exercise = DB::transaction(function () use ($data, $document, $request): Exercise {
            $payload = collect($data)->except('document')->all();
            $payload['created_by'] = $request->user()->id;
            $payload['published_at'] = $this->resolvePublishedAt($data, null);

            $exercise = Exercise::create($payload);

            if ($document) {
                $this->storeDocument($exercise, $document);
            }

            return $exercise;
        });

        if ($exercise->status === ExerciseStatus::Published) {
            $this->announcePublication($exercise, $activityLogger, $notifications);
        }

        return redirect()->route('supervisor.exercises.show', $exercise)->with('success', 'Exercice créé.');
    }

    public function show(Exercise $exercise): View
    {
        return view('supervisor.exercises.show', [
            'exercise' => $exercise->load(['theme.subject', 'attachments', 'creator']),
            'submissions' => $exercise->submissions()
                ->with(['student', 'latestGrade', 'files'])
                ->latest('submitted_at')
                ->paginate(20),
        ]);
    }

    public function edit(Exercise $exercise): View
    {
        return view('supervisor.exercises.edit', [
            ...$this->formData(),
            'exercise' => $exercise->load('attachments'),
        ]);
    }

    public function update(ExerciseRequest $request, Exercise $exercise, ActivityLogger $activityLogger, NotificationService $notifications): RedirectResponse
    {
        $data = $request->validated();
        $document = $request->file('document');
        $status = ExerciseStatus::from($data['status']);
        $wasPublished = $exercise->status === ExerciseStatus::Published;

        if ($data['type'] === ExerciseType::Pdf->value
            && $status === ExerciseStatus::Published
            && ! $document
            && ! $exercise->attachments()->exists()) {
            throw ValidationException::withMessages(['document' => 'Un exercice PDF publié doit contenir un document PDF.']);
        }

        DB::transaction(function () use ($exercise, $data, $document): void {
            $payload = collect($data)->except('document')->all();
            $payload['published_at'] = $this->resolvePublishedAt($data, $exercise);

            $exercise->update($payload);

            if ($document) {
                $this->storeDocument($exercise, $document);
            }
        });

        if (! $wasPublished && $exercise->fresh()->status === ExerciseStatus::Published) {
            $this->announcePublication($exercise->fresh(), $activityLogger, $notifications);
        }

        return redirect()->route('supervisor.exercises.show', $exercise)->with('success', 'Exercice mis à jour.');
    }

    public function publish(Exercise $exercise, ActivityLogger $activityLogger, NotificationService $notifications): RedirectResponse
    {
        if ($exercise->type === ExerciseType::Pdf && ! $exercise->attachments()->exists()) {
            return back()->withErrors(['document' => 'Ajoutez un document PDF avant de publier cet exercice.']);
        }

        $wasPublished = $exercise->status === ExerciseStatus::Published;

        $exercise->forceFill([
            'status' => ExerciseStatus::Published,
            'published_at' => $exercise->published_at ?? now(),
        ])->save();

        if (! $wasPublished) {
            $this->announcePublication($exercise, $activityLogger, $notifications);
        }

        return back()->with('success', 'Exercice publié. Les étudiants ont été notifiés.');
    }

    public function archive(Exercise $exercise): RedirectResponse
    {
        $exercise->forceFill(['status' => ExerciseStatus::Archived])->save();

        return back()->with('success', 'Exercice archivé.');
    }

    public function destroy(Exercise $exercise): RedirectResponse
    {
        $exercise->delete();

        return redirect()->route('supervisor.exercises.index')
            ->with('success', 'Exercice supprimé (archivé). Les corrections et soumissions sont conservées.');
    }

    public function destroyAttachment(ExerciseAttachment $attachment): RedirectResponse
    {
        Storage::disk('local')->delete($attachment->path);
        $exercise = $attachment->exercise;
        $attachment->delete();

        return back()->with('success', 'Document supprimé.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'subjects' => Subject::query()->whereNull('archived_at')->with(['themes' => fn ($query) => $query->whereNull('archived_at')->orderBy('position')])->orderBy('name')->get(),
            'themes' => Theme::query()->whereNull('archived_at')->with('subject')->orderBy('name')->get(),
            'types' => ExerciseType::cases(),
            'statuses' => ExerciseStatus::cases(),
        ];
    }

    private function resolvePublishedAt(array $data, ?Exercise $existing): mixed
    {
        $status = ExerciseStatus::from($data['status']);

        if ($status === ExerciseStatus::Published) {
            return $data['published_at'] ?? $existing?->published_at ?? now();
        }

        if ($status === ExerciseStatus::Archived) {
            return $data['published_at'] ?? $existing?->published_at;
        }

        return null;
    }

    private function storeDocument(Exercise $exercise, $document): void
    {
        $filename = Str::uuid()->toString().'.pdf';
        $path = $document->storeAs("exercises/{$exercise->id}", $filename, 'local');

        $exercise->attachments()->create([
            'path' => $path,
            'original_name' => $document->getClientOriginalName(),
            'mime_type' => 'application/pdf',
            'size' => $document->getSize(),
        ]);
    }

    private function announcePublication(Exercise $exercise, ActivityLogger $activityLogger, NotificationService $notifications): void
    {
        $notifications->exercisePublished($exercise);

        foreach (User::students()->active()->get() as $student) {
            $activityLogger->log(
                $student,
                'exercise_published',
                'Exercice attribué : '.$exercise->title,
                'Nouvel exercice disponible en '.$exercise->theme?->subject?->name.'.',
                $exercise,
            );
        }
    }
}
