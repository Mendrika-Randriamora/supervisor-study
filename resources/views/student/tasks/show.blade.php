@extends('layouts.app')

@section('title', $task->title)
@section('topbar-title', 'Détail de la tâche')

@section('content')
    @php $effective = $assignment->effectiveStatus(); @endphp

    <div class="page-header">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <h1 class="page-title mb-0">{{ $task->title }}</h1>
                <x-status-badge :status="$effective" />
                <span class="badge {{ $task->priority->badgeClass() }}">{{ $task->priority->label() }}</span>
            </div>
            <p class="page-subtitle mb-0">{{ $task->subject?->name }}@if ($task->theme) — {{ $task->theme->name }}@endif</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('student.tasks.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Mes tâches</a>
            @if ($task->exercise)
                <a href="{{ route('student.exercises.show', $task->exercise) }}" class="btn btn-primary btn-sm">Faire l’exercice</a>
            @elseif ($task->quiz)
                <a href="{{ route('student.quizzes.show', $task->quiz) }}" class="btn btn-primary btn-sm">Faire le quiz</a>
            @endif
        </div>
    </div>

    @if ($effective === \App\Enums\TaskStatus::Overdue)
        <div class="alert alert-danger d-flex gap-2 align-items-start">
            <i class="bi bi-alarm-fill mt-1"></i>
            <div>Cette tâche est en retard. Vous pouvez encore la terminer, elle sera alors marquée comme réalisée.</div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-xl-7">
            <div class="card mb-3">
                <div class="card-header">Description</div>
                <div class="card-body">
                    <p class="small mb-0" style="white-space: pre-line">{{ $task->description ?: 'Aucune description fournie.' }}</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Informations</div>
                <div class="card-body">
                    <dl class="detail-list">
                        <div class="detail-row"><dt>Matière</dt><dd>{{ $task->subject?->name ?? '—' }}</dd></div>
                        <div class="detail-row"><dt>Thème</dt><dd>{{ $task->theme?->name ?? '—' }}</dd></div>
                        <div class="detail-row"><dt>Date de début</dt><dd>{{ $task->starts_at?->format('d/m/Y') ?? '—' }}</dd></div>
                        <div class="detail-row"><dt>Échéance</dt><dd class="{{ $effective === \App\Enums\TaskStatus::Overdue ? 'text-danger' : '' }}">{{ $task->due_at?->format('d/m/Y H:i') ?? 'Aucune' }}</dd></div>
                        <div class="detail-row"><dt>Priorité</dt><dd>{{ $task->priority->label() }}</dd></div>
                        @if ($assignment->completed_at)
                            <div class="detail-row"><dt>Terminée le</dt><dd>{{ $assignment->completed_at->format('d/m/Y H:i') }}</dd></div>
                        @endif
                    </dl>

                    @if ($task->exercise || $task->quiz)
                        <div class="divider"></div>
                        <div class="small fw-semibold mb-2">Activité associée</div>
                        @if ($task->exercise)
                            <a href="{{ route('student.exercises.show', $task->exercise) }}" class="d-flex align-items-center gap-2 text-reset">
                                <i class="bi bi-journal-check text-primary fs-5"></i>
                                <div class="flex-grow-1">
                                    <div class="small fw-semibold">{{ $task->exercise->title }}</div>
                                    <div class="text-muted text-tiny">Exercice — {{ $task->exercise->type->shortLabel() }}</div>
                                </div>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </a>
                        @elseif ($task->quiz)
                            <a href="{{ route('student.quizzes.show', $task->quiz) }}" class="d-flex align-items-center gap-2 text-reset">
                                <i class="bi bi-patch-question text-primary fs-5"></i>
                                <div class="flex-grow-1">
                                    <div class="small fw-semibold">{{ $task->quiz->title }}</div>
                                    <div class="text-muted text-tiny">Quiz — {{ $task->quiz->total_points }} points</div>
                                </div>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </a>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card">
                <div class="card-header">Mon avancement</div>
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="small text-muted">Statut actuel</span>
                        <x-status-badge :status="$effective" />
                    </div>

                    <form method="POST" action="{{ route('student.tasks.status', $task) }}">
                        @csrf
                        @method('patch')

                        <div class="d-grid gap-2">
                            @foreach ([\App\Enums\TaskStatus::Todo, \App\Enums\TaskStatus::InProgress, \App\Enums\TaskStatus::Completed] as $statusOption)
                                <label class="quiz-option {{ $assignment->status === $statusOption ? 'border-primary bg-primary-soft' : '' }}" for="status-{{ $statusOption->value }}">
                                    <input class="form-check-input mt-0" type="radio" name="status" id="status-{{ $statusOption->value }}" value="{{ $statusOption->value }}" @checked($assignment->status === $statusOption)>
                                    <span class="flex-grow-1">
                                        <span class="d-block small fw-semibold">{{ $statusOption->label() }}</span>
                                        <span class="d-block text-muted text-tiny">
                                            @if ($statusOption === \App\Enums\TaskStatus::Todo)
                                                À commencer
                                            @elseif ($statusOption === \App\Enums\TaskStatus::InProgress)
                                                Travail en cours
                                            @else
                                                Travail achevé — la date est conservée
                                            @endif
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mt-3"><i class="bi bi-check2 me-1"></i>Mettre à jour mon statut</button>
                    </form>

                    @if ($assignment->completed_at)
                        <div class="alert alert-success py-2 small mt-3 mb-0">
                            <i class="bi bi-check2-circle me-1"></i>Tâche terminée le {{ $assignment->completed_at->format('d/m/Y à H:i') }}.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
