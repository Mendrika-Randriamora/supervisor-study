@extends('layouts.app')

@section('title', $task->title)
@section('topbar-title', 'Suivi de la tâche')

@section('content')
    @php
        $assignments = $task->assignments;
        $completed = $assignments->where('status', \App\Enums\TaskStatus::Completed)->count();
        $overdue = $assignments->filter(fn ($assignment) => $assignment->effectiveStatus() === \App\Enums\TaskStatus::Overdue)->count();
        $rate = $assignments->count() > 0 ? round(($completed / $assignments->count()) * 100, 1) : 0;
    @endphp

    <div class="page-header">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <h1 class="page-title mb-0">{{ $task->title }}</h1>
                <span class="badge {{ $task->priority->badgeClass() }}">{{ $task->priority->label() }}</span>
                @if ($overdue > 0)
                    <span class="badge text-bg-danger">{{ $overdue }} en retard</span>
                @endif
            </div>
            <p class="page-subtitle mb-0">{{ $task->subject?->name }}@if ($task->theme) — {{ $task->theme->name }}@endif</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('supervisor.tasks.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
            <a href="{{ route('supervisor.tasks.edit', $task) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Modifier</a>
            <form method="POST" action="{{ route('supervisor.tasks.destroy', $task) }}">
                @csrf @method('delete')
                <button class="btn btn-outline-danger btn-sm" type="submit" data-confirm="Supprimer cette tâche ? Les données restent conservées en base."><i class="bi bi-trash me-1"></i>Supprimer</button>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><x-stat-card label="Attribuée à" :value="$assignments->count()" icon="bi-people" hint="étudiant(s)" /></div>
        <div class="col-6 col-md-3"><x-stat-card label="Terminées" :value="$completed" icon="bi-check2-circle" tone="success" /></div>
        <div class="col-6 col-md-3"><x-stat-card label="En retard" :value="$overdue" icon="bi-alarm" :tone="$overdue > 0 ? 'danger' : 'success'" /></div>
        <div class="col-6 col-md-3"><x-stat-card label="Taux" :value="number_format($rate, 1).' %'" icon="bi-graph-up" hint="de réalisation" /></div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-4">
            <div class="card mb-3">
                <div class="card-header">Détails</div>
                <div class="card-body">
                    <p class="small" style="white-space: pre-line">{{ $task->description ?: 'Aucune description.' }}</p>
                    <dl class="detail-list">
                        <div class="detail-row"><dt>Matière</dt><dd>{{ $task->subject?->name ?? '—' }}</dd></div>
                        <div class="detail-row"><dt>Thème</dt><dd>{{ $task->theme?->name ?? '—' }}</dd></div>
                        <div class="detail-row"><dt>Début</dt><dd>{{ $task->starts_at?->format('d/m/Y') ?? '—' }}</dd></div>
                        <div class="detail-row"><dt>Échéance</dt><dd class="{{ $task->isOverdue() ? 'text-danger' : '' }}">{{ $task->due_at?->format('d/m/Y H:i') ?? 'Aucune' }}</dd></div>
                        <div class="detail-row"><dt>Créée le</dt><dd>{{ $task->created_at?->format('d/m/Y') }}</dd></div>
                    </dl>

                    @if ($task->exercise || $task->quiz)
                        <div class="divider"></div>
                        <div class="small fw-semibold mb-2">Activité liée</div>
                        @if ($task->exercise)
                            <a href="{{ route('supervisor.exercises.show', $task->exercise) }}" class="d-flex align-items-center gap-2 text-reset">
                                <i class="bi bi-journal-check text-primary"></i>
                                <span class="small">{{ $task->exercise->title }}</span>
                                <i class="bi bi-chevron-right text-muted ms-auto"></i>
                            </a>
                        @elseif ($task->quiz)
                            <a href="{{ route('supervisor.quizzes.show', $task->quiz) }}" class="d-flex align-items-center gap-2 text-reset">
                                <i class="bi bi-patch-question text-primary"></i>
                                <span class="small">{{ $task->quiz->title }}</span>
                                <i class="bi bi-chevron-right text-muted ms-auto"></i>
                            </a>
                        @endif
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">Progression globale</div>
                <div class="card-body">
                    <div class="d-flex align-items-baseline gap-2 mb-2">
                        <span class="stat-value">{{ number_format($rate, 1) }}%</span>
                        <span class="text-muted small">{{ $completed }} / {{ $assignments->count() }}</span>
                    </div>
                    <div class="progress"><div class="progress-bar bg-success" style="width: {{ $rate }}%"></div></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header">Étudiants assignés</div>
                <div class="card-body p-0">
                    @if ($assignments->isEmpty())
                        <x-empty-state icon="bi-people" title="Aucun étudiant assigné" text="Modifiez la tâche pour l’attribuer à des étudiants." />
                    @else
                        <div class="table-responsive table-desktop">
                            <table class="table table-hover">
                                <thead>
                                    <tr><th>Étudiant</th><th>Statut</th><th>Terminée le</th><th></th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($assignments->sortBy(fn ($assignment) => $assignment->student?->name) as $assignment)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="avatar">{{ $assignment->student?->initials() }}</span>
                                                    <div>
                                                        <div class="fw-semibold small">{{ $assignment->student?->name }}</div>
                                                        <div class="text-muted text-tiny">{{ $assignment->student?->email }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><x-status-badge :status="$assignment->effectiveStatus()" /></td>
                                            <td class="text-muted text-tiny">{{ $assignment->completed_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                            <td class="text-end">
                                                @if ($assignment->student)
                                                    <a href="{{ route('supervisor.students.show', $assignment->student) }}" class="btn btn-sm btn-outline-secondary">Fiche</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mobile-list p-2">
                            @foreach ($assignments as $assignment)
                                <div class="mobile-list-item">
                                    <div class="d-flex justify-content-between gap-2">
                                        <span class="fw-semibold small">{{ $assignment->student?->name }}</span>
                                        <x-status-badge :status="$assignment->effectiveStatus()" />
                                    </div>
                                    <div class="meta-line mt-1">
                                        @if ($assignment->completed_at)
                                            <span>Terminée le {{ $assignment->completed_at->format('d/m/Y H:i') }}</span>
                                        @elseif ($task->due_at)
                                            <span>Échéance {{ $task->due_at->format('d/m/Y H:i') }}</span>
                                        @endif
                                    </div>
                                    @if ($assignment->student)
                                        <a href="{{ route('supervisor.students.show', $assignment->student) }}" class="btn btn-sm btn-outline-secondary w-100 mt-2">Voir la fiche</a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
