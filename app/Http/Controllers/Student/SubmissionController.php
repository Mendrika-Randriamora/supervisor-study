<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExerciseSubmission;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function show(ExerciseSubmission $submission): View
    {
        $this->authorize('view', $submission);

        return view('student.submissions.show', [
            'submission' => $submission->load([
                'exercise.theme.subject',
                'files.annotations',
                'grades.grader',
                'latestGrade',
            ]),
            'history' => $submission->exercise->submissions()
                ->where('user_id', $submission->user_id)
                ->with('latestGrade')
                ->latest('version')
                ->get(),
        ]);
    }
}
