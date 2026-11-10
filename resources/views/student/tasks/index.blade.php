@extends('layouts.app')

@section('title', 'Mes tâches')
@section('topbar-title', 'Mes tâches')

@section('content')
    <x-page-header title="Mes tâches" subtitle="Organisez votre travail et cochez les tâches terminées." />

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('student.tasks.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label" for="subject">Matière</label>
                    <select id="subject" name="subject" class="form-select" data-auto-submit>
                        <option value="">Toutes les matières</option>
                        @foreach ($subjects as $subjectItem)
                            <option value="{{ $subjectItem->id }}" @selected($selectedSubject == $subjectItem->id)>{{ $subjectItem->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-7 col-md-4">
                    <label class="form-label" for="status">Statut</label>
                    <select id="status" name="status" class="form-select" data-auto-submit>
                        <option value="">Toutes</option>
                        <option value="open" @selected($status === 'open')>Ouvertes</option>
                        <option value="overdue" @selected($status === 'overdue')>En retard</option>
                        <option value="completed" @selected($status === 'completed')>Terminées</option>
                    </select>
                </div>
                <div class="col-5 col-md-3">
                    <button class="btn btn-outline-primary w-100" type="submit">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>{{ $assignments->total() }} tâche(s)</span>
            <span class="text-muted text-tiny">Page {{ $assignments->currentPage() }} / {{ $assignments->lastPage() }}</span>
        </div>
        <div class="card-body p-0">
            @if ($assignments->isEmpty())
                <x-empty-state icon="bi-list-task" title="Aucune tâche" text="Aucune tâche ne correspond à ce filtre." />
            @else
                <div class="table-responsive table-desktop">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>Tâche</th><th>Matière</th><th>Priorité</th><th>Statut</th><th>Échéance</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($assignments as $assignment)
                                @php
                                    $task = $assignment->task;
                                    $effective = $assignment->effectiveStatus();
                                @endphp
                                <tr class="hover-row">
                                    <td>
                                        <a href="{{ route('student.tasks.show', $task) }}" class="fw-semibold text-reset">{{ Str::limit($task?->title, 50) }}</a>
                                        @if ($task?->exercise || $task?->quiz)
                                            <div class="text-muted text-tiny">
                                                <i class="bi {{ $task->exercise ? 'bi-journal-check' : 'bi-patch-question' }} me-1"></i>
                                                Activité liée
                                            </div>
                                        @endif
                                    </td>
                                    <td class="small">{{ $task?->subject?->name }}</td>
                                    <td><span class="badge {{ $task?->priority?->badgeClass() }}">{{ $task?->priority?->label() }}</span></td>
                                    <td><x-status-badge :status="$effective" /></td>
                                    <td class="text-tiny {{ $effective === \App\Enums\TaskStatus::Overdue ? 'text-danger fw-semibold' : 'text-muted' }}">
                                        {{ $task?->due_at?->format('d/m/Y H:i') ?? 'Aucune' }}
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="{{ route('student.tasks.show', $task) }}" class="btn btn-sm btn-outline-secondary">Ouvrir</a>
                                            @if ($assignment->status === \App\Enums\TaskStatus::Completed)
                                                <form method="POST" action="{{ route('student.tasks.status', $task) }}">
                                                    @csrf @method('patch')
                                                    <input type="hidden" name="status" value="todo">
                                                    <button class="btn btn-sm btn-outline-warning" type="submit" data-confirm="Rouvrir cette tâche ?">Rouvrir</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('student.tasks.status', $task) }}">
                                                    @csrf @method('patch')
                                                    <input type="hidden" name="status" value="completed">
                                                    <button class="btn btn-sm btn-outline-success" type="submit">Terminer</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mobile-list p-2">
                    @foreach ($assignments as $assignment)
                        @php
                            $task = $assignment->task;
                            $effective = $assignment->effectiveStatus();
                        @endphp
                        <div class="mobile-list-item">
                            <div class="d-flex justify-content-between gap-2">
                                <a href="{{ route('student.tasks.show', $task) }}" class="fw-semibold small text-reset">{{ Str::limit($task?->title, 60) }}</a>
                                <x-status-badge :status="$effective" />
                            </div>
                            <div class="meta-line mt-1">
                                <span>{{ $task?->subject?->name }}</span>
                                <span>{{ $task?->priority?->label() }}</span>
                                @if ($task?->due_at)
                                    <span class="{{ $effective === \App\Enums\TaskStatus::Overdue ? 'text-danger' : '' }}"><i class="bi bi-clock me-1"></i>{{ $task->due_at->format('d/m/Y H:i') }}</span>
                                @endif
                            </div>
                            <div class="d-flex gap-2 mt-2">
                                <a href="{{ route('student.tasks.show', $task) }}" class="btn btn-sm btn-outline-secondary flex-grow-1">Ouvrir</a>
                                <form method="POST" action="{{ route('student.tasks.status', $task) }}">
                                    @csrf @method('patch')
                                    @if ($assignment->status === \App\Enums\TaskStatus::Completed)
                                        <input type="hidden" name="status" value="todo">
                                        <button class="btn btn-sm btn-outline-warning" type="submit">Rouvrir</button>
                                    @else
                                        <input type="hidden" name="status" value="completed">
                                        <button class="btn btn-sm btn-outline-success" type="submit">Terminer</button>
                                    @endif
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="p-3">{{ $assignments->links() }}</div>
            @endif
        </div>
    </div>
@endsection
