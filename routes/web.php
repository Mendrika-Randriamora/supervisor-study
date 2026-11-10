<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\ExerciseController as StudentExerciseController;
use App\Http\Controllers\Student\HistoryController as StudentHistoryController;
use App\Http\Controllers\Student\QuizController as StudentQuizController;
use App\Http\Controllers\Student\ResultController as StudentResultController;
use App\Http\Controllers\Student\SubjectController as StudentSubjectController;
use App\Http\Controllers\Student\SubmissionController as StudentSubmissionController;
use App\Http\Controllers\Student\TaskController as StudentTaskController;
use App\Http\Controllers\Supervisor\ExerciseController as SupervisorExerciseController;
use App\Http\Controllers\Supervisor\HistoryController as SupervisorHistoryController;
use App\Http\Controllers\Supervisor\NotificationController as SupervisorNotificationController;
use App\Http\Controllers\Supervisor\QuizController as SupervisorQuizController;
use App\Http\Controllers\Supervisor\QuizQuestionController;
use App\Http\Controllers\Supervisor\StudentController as SupervisorStudentController;
use App\Http\Controllers\Supervisor\SubjectController as SupervisorSubjectController;
use App\Http\Controllers\Supervisor\SubmissionController as SupervisorSubmissionController;
use App\Http\Controllers\Supervisor\TaskController as SupervisorTaskController;
use App\Http\Controllers\Supervisor\ThemeController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'role:supervisor,student'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/calendrier', [CalendarController::class, 'index'])->name('calendar.index');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}/ouvrir', [NotificationController::class, 'open'])->name('notifications.open');
    Route::patch('/notifications/{notification}/lue', [NotificationController::class, 'read'])->name('notifications.read');
    Route::patch('/notifications/tout-lire', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    Route::get('/fichiers/exercices/{attachment}', [FileController::class, 'exerciseAttachment'])->name('files.exercise');
    Route::get('/fichiers/soumissions/{file}', [FileController::class, 'submissionFile'])->name('files.submission');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'role:supervisor'])
    ->prefix('superviseur')
    ->name('supervisor.')
    ->group(function () {
        Route::get('etudiants', [SupervisorStudentController::class, 'index'])->name('students.index');
        Route::get('etudiants/creer', [SupervisorStudentController::class, 'create'])->name('students.create');
        Route::post('etudiants', [SupervisorStudentController::class, 'store'])->name('students.store');
        Route::get('etudiants/{student}', [SupervisorStudentController::class, 'show'])->name('students.show');
        Route::get('etudiants/{student}/modifier', [SupervisorStudentController::class, 'edit'])->name('students.edit');
        Route::patch('etudiants/{student}', [SupervisorStudentController::class, 'update'])->name('students.update');
        Route::patch('etudiants/{student}/desactiver', [SupervisorStudentController::class, 'deactivate'])->name('students.deactivate');
        Route::patch('etudiants/{student}/activer', [SupervisorStudentController::class, 'activate'])->name('students.activate');
        Route::post('etudiants/{student}/mot-de-passe', [SupervisorStudentController::class, 'resetPassword'])->name('students.reset-password');
        Route::delete('etudiants/{student}', [SupervisorStudentController::class, 'destroy'])->name('students.destroy');

        Route::get('matieres', [SupervisorSubjectController::class, 'index'])->name('subjects.index');
        Route::get('matieres/creer', [SupervisorSubjectController::class, 'create'])->name('subjects.create');
        Route::post('matieres', [SupervisorSubjectController::class, 'store'])->name('subjects.store');
        Route::get('matieres/{subject}', [SupervisorSubjectController::class, 'show'])->name('subjects.show');
        Route::get('matieres/{subject}/modifier', [SupervisorSubjectController::class, 'edit'])->name('subjects.edit');
        Route::patch('matieres/{subject}', [SupervisorSubjectController::class, 'update'])->name('subjects.update');
        Route::patch('matieres/{subject}/archiver', [SupervisorSubjectController::class, 'archive'])->name('subjects.archive');
        Route::patch('matieres/{subject}/reactiver', [SupervisorSubjectController::class, 'unarchive'])->name('subjects.unarchive');
        Route::delete('matieres/{subject}', [SupervisorSubjectController::class, 'destroy'])->name('subjects.destroy');

        Route::post('matieres/{subject}/themes', [ThemeController::class, 'store'])->name('themes.store');
        Route::patch('themes/{theme}', [ThemeController::class, 'update'])->name('themes.update');
        Route::patch('themes/{theme}/archiver', [ThemeController::class, 'archive'])->name('themes.archive');
        Route::patch('themes/{theme}/reactiver', [ThemeController::class, 'unarchive'])->name('themes.unarchive');
        Route::delete('themes/{theme}', [ThemeController::class, 'destroy'])->name('themes.destroy');

        Route::get('exercices', [SupervisorExerciseController::class, 'index'])->name('exercises.index');
        Route::get('exercices/creer', [SupervisorExerciseController::class, 'create'])->name('exercises.create');
        Route::post('exercices', [SupervisorExerciseController::class, 'store'])->name('exercises.store');
        Route::get('exercices/{exercise}', [SupervisorExerciseController::class, 'show'])->name('exercises.show');
        Route::get('exercices/{exercise}/modifier', [SupervisorExerciseController::class, 'edit'])->name('exercises.edit');
        Route::patch('exercices/{exercise}', [SupervisorExerciseController::class, 'update'])->name('exercises.update');
        Route::patch('exercices/{exercise}/publier', [SupervisorExerciseController::class, 'publish'])->name('exercises.publish');
        Route::patch('exercices/{exercise}/archiver', [SupervisorExerciseController::class, 'archive'])->name('exercises.archive');
        Route::delete('exercices/{exercise}', [SupervisorExerciseController::class, 'destroy'])->name('exercises.destroy');
        Route::delete('documents/{attachment}', [SupervisorExerciseController::class, 'destroyAttachment'])->name('exercises.attachments.destroy');

        Route::get('soumissions', [SupervisorSubmissionController::class, 'index'])->name('submissions.index');
        Route::get('soumissions/{submission}', [SupervisorSubmissionController::class, 'show'])->name('submissions.show');
        Route::post('soumissions/{submission}/noter', [SupervisorSubmissionController::class, 'grade'])->name('submissions.grade');
        Route::post('soumissions/{submission}/nouvelle-reponse', [SupervisorSubmissionController::class, 'requestRevision'])->name('submissions.revision');
        Route::post('soumissions/{submission}/fichiers/{file}/annotations', [SupervisorSubmissionController::class, 'annotate'])->name('submissions.annotate');
        Route::delete('soumissions/{submission}/annotations/{annotation}', [SupervisorSubmissionController::class, 'destroyAnnotation'])->name('submissions.annotations.destroy');

        Route::get('quiz', [SupervisorQuizController::class, 'index'])->name('quizzes.index');
        Route::get('quiz/creer', [SupervisorQuizController::class, 'create'])->name('quizzes.create');
        Route::post('quiz', [SupervisorQuizController::class, 'store'])->name('quizzes.store');
        Route::get('quiz/{quiz}', [SupervisorQuizController::class, 'show'])->name('quizzes.show');
        Route::get('quiz/{quiz}/modifier', [SupervisorQuizController::class, 'edit'])->name('quizzes.edit');
        Route::patch('quiz/{quiz}', [SupervisorQuizController::class, 'update'])->name('quizzes.update');
        Route::patch('quiz/{quiz}/publier', [SupervisorQuizController::class, 'publish'])->name('quizzes.publish');
        Route::patch('quiz/{quiz}/archiver', [SupervisorQuizController::class, 'archive'])->name('quizzes.archive');
        Route::delete('quiz/{quiz}', [SupervisorQuizController::class, 'destroy'])->name('quizzes.destroy');

        Route::post('quiz/{quiz}/questions', [QuizQuestionController::class, 'store'])->name('quizzes.questions.store');
        Route::patch('quiz/{quiz}/questions/{question}', [QuizQuestionController::class, 'update'])->name('quizzes.questions.update');
        Route::delete('quiz/{quiz}/questions/{question}', [QuizQuestionController::class, 'destroy'])->name('quizzes.questions.destroy');

        Route::get('taches', [SupervisorTaskController::class, 'index'])->name('tasks.index');
        Route::get('taches/creer', [SupervisorTaskController::class, 'create'])->name('tasks.create');
        Route::post('taches', [SupervisorTaskController::class, 'store'])->name('tasks.store');
        Route::get('taches/{task}', [SupervisorTaskController::class, 'show'])->name('tasks.show');
        Route::get('taches/{task}/modifier', [SupervisorTaskController::class, 'edit'])->name('tasks.edit');
        Route::patch('taches/{task}', [SupervisorTaskController::class, 'update'])->name('tasks.update');
        Route::delete('taches/{task}', [SupervisorTaskController::class, 'destroy'])->name('tasks.destroy');

        Route::get('notifications/creer', [SupervisorNotificationController::class, 'create'])->name('notifications.create');
        Route::post('notifications', [SupervisorNotificationController::class, 'store'])->name('notifications.store');

        Route::get('historique', [SupervisorHistoryController::class, 'index'])->name('history.index');
    });

Route::middleware(['auth', 'role:student'])
    ->prefix('etudiant')
    ->name('student.')
    ->group(function () {
        Route::get('matieres', [StudentSubjectController::class, 'index'])->name('subjects.index');
        Route::get('matieres/{subject}', [StudentSubjectController::class, 'show'])->name('subjects.show');

        Route::get('exercices', [StudentExerciseController::class, 'index'])->name('exercises.index');
        Route::get('exercices/{exercise}', [StudentExerciseController::class, 'show'])->name('exercises.show');
        Route::post('exercices/{exercise}/soumettre', [StudentExerciseController::class, 'submit'])->name('exercises.submit');
        Route::get('soumissions/{submission}', [StudentSubmissionController::class, 'show'])->name('submissions.show');

        Route::get('quiz', [StudentQuizController::class, 'index'])->name('quizzes.index');
        Route::get('quiz/{quiz}', [StudentQuizController::class, 'show'])->name('quizzes.show');
        Route::post('quiz/{quiz}/commencer', [StudentQuizController::class, 'start'])->name('quizzes.start');
        Route::get('quiz/{quiz}/passer', [StudentQuizController::class, 'take'])->name('quizzes.take');
        Route::post('quiz/{quiz}/soumettre', [StudentQuizController::class, 'submit'])->name('quizzes.submit');
        Route::get('quiz/{quiz}/resultat/{attempt}', [StudentQuizController::class, 'result'])->name('quizzes.result');

        Route::get('resultats', [StudentResultController::class, 'index'])->name('results.index');
        Route::get('resultats/{quiz}', [StudentResultController::class, 'show'])->name('results.show');

        Route::get('taches', [StudentTaskController::class, 'index'])->name('tasks.index');
        Route::get('taches/{task}', [StudentTaskController::class, 'show'])->name('tasks.show');
        Route::patch('taches/{task}/statut', [StudentTaskController::class, 'updateStatus'])->name('tasks.status');

        Route::get('historique', [StudentHistoryController::class, 'index'])->name('history.index');
    });

require __DIR__.'/auth.php';
