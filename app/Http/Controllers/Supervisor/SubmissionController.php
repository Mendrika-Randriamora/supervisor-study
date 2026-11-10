<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\AnnotationType;
use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AnnotationRequest;
use App\Http\Requests\GradeSubmissionRequest;
use App\Models\Exercise;
use App\Models\ExerciseGrade;
use App\Models\ExerciseSubmission;
use App\Models\SubmissionAnnotation;
use App\Models\SubmissionFile;
use App\Services\ActivityLogger;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $exerciseId = $request->integer('exercise') ?: null;

        $submissions = ExerciseSubmission::query()
            ->with(['exercise.theme.subject', 'student', 'latestGrade', 'files'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($exerciseId, fn ($query) => $query->where('exercise_id', $exerciseId))
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('supervisor.submissions.index', [
            'submissions' => $submissions,
            'statuses' => SubmissionStatus::cases(),
            'status' => $status,
            'exercises' => Exercise::with('theme')->orderByDesc('created_at')->limit(100)->get(),
            'selectedExercise' => $exerciseId,
        ]);
    }

    public function show(ExerciseSubmission $submission): View
    {
        $submission->load([
            'exercise.theme.subject',
            'student',
            'files.annotations',
            'grades.grader',
            'latestGrade',
        ]);

        return view('supervisor.submissions.show', [
            'submission' => $submission,
            'versions' => ExerciseSubmission::query()
                ->where('exercise_id', $submission->exercise_id)
                ->where('user_id', $submission->user_id)
                ->with('latestGrade')
                ->latest('version')
                ->get(),
        ]);
    }

    public function grade(
        GradeSubmissionRequest $request,
        ExerciseSubmission $submission,
        ActivityLogger $activityLogger,
        NotificationService $notifications,
    ): RedirectResponse {
        $submission->loadMissing('exercise.theme.subject');
        $max = (float) $submission->exercise->max_points;

        if ((float) $request->validated('score') > $max) {
            throw ValidationException::withMessages([
                'score' => "La note ne peut pas dépasser le barème de {$max} points.",
            ]);
        }

        DB::transaction(function () use ($request, $submission): void {
            ExerciseSubmission::whereKey($submission->id)->lockForUpdate()->first();

            ExerciseGrade::create([
                'submission_id' => $submission->id,
                'graded_by' => $request->user()->id,
                'score' => $request->validated('score'),
                'max_points' => $submission->exercise->max_points,
                'comment' => $request->validated('comment'),
                'graded_at' => now(),
            ]);

            $submission->forceFill([
                'status' => SubmissionStatus::Graded,
                'graded_at' => now(),
            ])->save();
        });

        $notifications->graded(
            $submission->student,
            $submission->exercise->title,
            route('student.submissions.show', $submission),
            $request->validated('comment'),
        );

        $activityLogger->log(
            $submission->student,
            'exercise_graded',
            'Exercice corrigé : '.$submission->exercise->title,
            'Note : '.$request->validated('score').' / '.$submission->exercise->max_points,
            $submission,
            ['score' => (float) $request->validated('score'), 'max_points' => (float) $submission->exercise->max_points],
        );

        return back()->with('success', 'Correction enregistrée et étudiant notifié.');
    }

    public function requestRevision(
        Request $request,
        ExerciseSubmission $submission,
        ActivityLogger $activityLogger,
        NotificationService $notifications,
    ): RedirectResponse {
        $data = $request->validate([
            'comment' => ['required', 'string', 'max:5000'],
        ]);

        $submission->forceFill([
            'status' => SubmissionStatus::RevisionRequested,
            'revision_comment' => $data['comment'],
        ])->save();

        $notifications->revisionRequested(
            $submission->student,
            $submission->exercise->title,
            route('student.submissions.show', $submission),
            $data['comment'],
        );

        $activityLogger->log(
            $submission->student,
            'revision_requested',
            'Nouvelle réponse demandée : '.$submission->exercise->title,
            $data['comment'],
            $submission,
        );

        return back()->with('success', 'Nouvelle soumission demandée à l’étudiant.');
    }

    public function annotate(
        AnnotationRequest $request,
        ExerciseSubmission $submission,
        SubmissionFile $file,
        ActivityLogger $activityLogger,
        NotificationService $notifications,
    ): RedirectResponse {
        abort_unless($file->submission_id === $submission->id, 404);

        $comment = $request->validated('comment');

        SubmissionAnnotation::updateOrCreate(
            [
                'submission_file_id' => $file->id,
                'created_by' => $request->user()->id,
                'type' => AnnotationType::Overlay,
            ],
            [
                'data' => $request->validated('data'),
                'comment' => $comment,
            ],
        );

        if (filled($comment)) {
            $submission->loadMissing(['student', 'exercise']);

            $notifications->commentAdded(
                $submission->student,
                $submission->exercise->title,
                route('student.submissions.show', $submission),
                $comment,
            );

            $activityLogger->log(
                $submission->student,
                'comment_added',
                'Commentaire ajouté : '.$submission->exercise->title,
                $comment,
                $submission,
            );
        }

        return back()->with('success', 'Annotations enregistrées. L’image originale reste intacte.');
    }

    public function destroyAnnotation(ExerciseSubmission $submission, SubmissionAnnotation $annotation): RedirectResponse
    {
        abort_unless($annotation->file->submission_id === $submission->id, 404);
        $annotation->delete();

        return back()->with('success', 'Annotations supprimées.');
    }
}
