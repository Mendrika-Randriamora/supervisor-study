<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\SubmissionStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\User;
use App\Services\StatisticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->toString();
        $status = $request->string('status')->toString();

        $students = User::students()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->withCount(['submissions', 'quizAttempts'])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('supervisor.students.index', compact('students', 'search', 'status'));
    }

    public function create(): View
    {
        return view('supervisor.students.create');
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $student = User::create([
            ...$request->safe()->only(['name', 'email', 'phone', 'notes']),
            'password' => Hash::make($request->validated('password')),
            'role' => UserRole::Student,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return redirect()->route('supervisor.students.show', $student)
            ->with('success', 'Compte étudiant créé. Communiquez ses identifiants à l’étudiant.');
    }

    public function show(User $student, StatisticsService $statistics): View
    {
        $this->ensureStudent($student);

        return view('supervisor.students.show', [
            'student' => $student,
            'overview' => $statistics->studentOverview($student),
            'submissions' => $student->submissions()->with(['exercise.theme.subject', 'latestGrade'])->latest('submitted_at')->limit(10)->get(),
            'attempts' => $student->quizAttempts()->with('quiz.theme.subject')->latest('submitted_at')->limit(10)->get(),
            'pendingCount' => $student->submissions()->where('status', SubmissionStatus::Submitted)->count(),
            'events' => $student->activityEvents()->latest('occurred_at')->limit(10)->get(),
        ]);
    }

    public function edit(User $student): View
    {
        $this->ensureStudent($student);

        return view('supervisor.students.edit', compact('student'));
    }

    public function update(UpdateStudentRequest $request, User $student): RedirectResponse
    {
        $this->ensureStudent($student);
        $student->update($request->validated());

        return redirect()->route('supervisor.students.show', $student)->with('success', 'Profil de l’étudiant mis à jour.');
    }

    public function deactivate(User $student): RedirectResponse
    {
        $this->ensureStudent($student);
        $student->forceFill(['is_active' => false])->save();

        return back()->with('success', 'Compte désactivé. L’étudiant ne peut plus se connecter.');
    }

    public function activate(User $student): RedirectResponse
    {
        $this->ensureStudent($student);
        $student->forceFill(['is_active' => true])->save();

        return back()->with('success', 'Compte réactivé.');
    }

    public function resetPassword(User $student): RedirectResponse
    {
        $this->ensureStudent($student);
        $password = Str::password(12, symbols: false);
        $student->forceFill(['password' => Hash::make($password)])->save();

        return back()->with('success', 'Mot de passe réinitialisé.')
            ->with('generated_password', $password);
    }

    public function destroy(User $student): RedirectResponse
    {
        $this->ensureStudent($student);
        $student->forceFill(['is_active' => false])->save();
        $student->delete();

        return redirect()->route('supervisor.students.index')
            ->with('success', 'Compte étudiant supprimé (archivé). Son historique académique est conservé.');
    }

    private function ensureStudent(User $student): void
    {
        abort_unless($student->isStudent(), 404);
    }
}
