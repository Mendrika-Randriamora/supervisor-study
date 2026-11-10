@php
    $user = auth()->user();
    $mobile = $mobile ?? false;
    $unread = $user->unreadNotifications()->count();
    $pendingSubmissions = $user->isSupervisor()
        ? \App\Models\ExerciseSubmission::where('status', \App\Enums\SubmissionStatus::Submitted)->count()
        : 0;
@endphp

<div class="sidebar-brand">
    <span class="brand-mark"><i class="bi bi-mortarboard-fill"></i></span>
    <div class="flex-grow-1">
        <div class="brand-name">{{ config('app.name') }}</div>
        <div class="brand-sub">{{ $user->isSupervisor() ? 'Superviseur' : 'Étudiant' }}</div>
    </div>
    @if ($mobile)
        <span></span>
    @endif
</div>

<div class="sidebar-scroll">
    <div class="nav-label">Navigation</div>
    <nav class="sidebar-nav">
        <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2"></i><span>Tableau de bord</span>
        </a>

        @if ($user->isSupervisor())
            <a href="{{ route('supervisor.students.index') }}" class="sidebar-link {{ request()->routeIs('supervisor.students.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i><span>Étudiants</span>
            </a>
            <a href="{{ route('supervisor.subjects.index') }}" class="sidebar-link {{ request()->routeIs('supervisor.subjects.*', 'supervisor.themes.*') ? 'active' : '' }}">
                <i class="bi bi-journal-bookmark"></i><span>Matières</span>
            </a>
            <a href="{{ route('supervisor.exercises.index') }}" class="sidebar-link {{ request()->routeIs('supervisor.exercises.*') ? 'active' : '' }}">
                <i class="bi bi-journal-check"></i><span>Exercices</span>
            </a>
            <a href="{{ route('supervisor.submissions.index') }}" class="sidebar-link {{ request()->routeIs('supervisor.submissions.*') ? 'active' : '' }}">
                <i class="bi bi-inbox"></i><span>Soumissions</span>
                @if ($pendingSubmissions > 0)
                    <span class="nav-badge alert-badge">{{ $pendingSubmissions }}</span>
                @endif
            </a>
            <a href="{{ route('supervisor.quizzes.index') }}" class="sidebar-link {{ request()->routeIs('supervisor.quizzes.*') ? 'active' : '' }}">
                <i class="bi bi-patch-question"></i><span>Quiz</span>
            </a>
            <a href="{{ route('supervisor.tasks.index') }}" class="sidebar-link {{ request()->routeIs('supervisor.tasks.*') ? 'active' : '' }}">
                <i class="bi bi-list-task"></i><span>Tâches</span>
            </a>
        @else
            <a href="{{ route('student.subjects.index') }}" class="sidebar-link {{ request()->routeIs('student.subjects.*') ? 'active' : '' }}">
                <i class="bi bi-journal-bookmark"></i><span>Mes matières</span>
            </a>
            <a href="{{ route('student.exercises.index') }}" class="sidebar-link {{ request()->routeIs('student.exercises.*', 'student.submissions.*') ? 'active' : '' }}">
                <i class="bi bi-journal-check"></i><span>Mes exercices</span>
            </a>
            <a href="{{ route('student.quizzes.index') }}" class="sidebar-link {{ request()->routeIs('student.quizzes.*') ? 'active' : '' }}">
                <i class="bi bi-patch-question"></i><span>Mes quiz</span>
            </a>
            <a href="{{ route('student.tasks.index') }}" class="sidebar-link {{ request()->routeIs('student.tasks.*') ? 'active' : '' }}">
                <i class="bi bi-list-task"></i><span>Mes tâches</span>
            </a>
            <a href="{{ route('student.results.index') }}" class="sidebar-link {{ request()->routeIs('student.results.*') ? 'active' : '' }}">
                <i class="bi bi-award"></i><span>Mes résultats</span>
            </a>
        @endif

        <a href="{{ route('calendar.index') }}" class="sidebar-link {{ request()->routeIs('calendar.*') ? 'active' : '' }}">
            <i class="bi bi-calendar3"></i><span>Calendrier</span>
        </a>
        <a href="{{ route('notifications.index') }}" class="sidebar-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
            <i class="bi bi-bell"></i><span>Notifications</span>
            @if ($unread > 0)
                <span class="nav-badge alert-badge">{{ $unread }}</span>
            @endif
        </a>
        <a href="{{ $user->isSupervisor() ? route('supervisor.history.index') : route('student.history.index') }}" class="sidebar-link {{ request()->routeIs('supervisor.history.*', 'student.history.*') ? 'active' : '' }}">
            <i class="bi bi-clock-history"></i><span>Historique</span>
        </a>

        @if ($user->isSupervisor())
            <div class="nav-label mt-1">Communication</div>
            <a href="{{ route('supervisor.notifications.create') }}" class="sidebar-link {{ request()->routeIs('supervisor.notifications.*') ? 'active' : '' }}">
                <i class="bi bi-megaphone"></i><span>Envoyer un message</span>
            </a>
        @endif
    </nav>
</div>

<div class="sidebar-footer">
    <a href="{{ route('profile.edit') }}" class="sidebar-user text-reset">
        <span class="avatar">{{ $user->initials() }}</span>
        <span class="flex-grow-1 overflow-hidden">
            <span class="d-block small fw-semibold text-truncate">{{ $user->name }}</span>
            <span class="d-block text-muted text-tiny text-truncate">{{ $user->email }}</span>
        </span>
        <i class="bi bi-gear text-muted"></i>
    </a>
    <form method="POST" action="{{ route('logout') }}" class="mt-2">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-box-arrow-right me-1"></i>Déconnexion</button>
    </form>
</div>
