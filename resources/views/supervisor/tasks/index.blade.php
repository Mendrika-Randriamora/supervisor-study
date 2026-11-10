@extends('layouts.app')

@section('title', 'Tâches')
@section('topbar-title', 'Gestion des tâches')

@section('content')
    <x-page-header title="Tâches" subtitle="Attribuez des tâches aux étudiants et suivez leur avancement.">
        <x-slot:actions>
            <a href="{{ route('supervisor.tasks.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nouvelle tâche</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('supervisor.tasks.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="subject">Matière</label>
                    <select id="subject" name="subject" class="form-select" data-auto-submit>
                        <option value="">Toutes</option>
                        @foreach ($subjects as $subjectItem)
                            <option value="{{ $subjectItem->id }}" @selected($selectedSubject == $subjectItem->id)>{{ $subjectItem->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="student">Étudiant</label>
                    <select id="student" name="student" class="form-select" data-auto-submit>
                        <option value="">Tous</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" @selected($selectedStudent == $student->id)>{{ $student->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-8 col-md-2">
                    <label class="form-label" for="status">Avancement</label>
                    <select id="status" name="status" class="form-select" data-auto-submit>
                        <option value="">Toutes</option>
                        <option value="open" @selected($status === 'open')>En cours</option>
                        <option value="overdue" @selected($status === 'overdue')>En retard</option>
                        <option value="completed" @selected($status === 'completed')>Terminées</option>
                    </select>
                </div>
                <div class="col-4 col-md-2">
                    <button class="btn btn-outline-primary w-100" type="submit">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>{{ $tasks->total() }} tâche(s)</span>
            <span class="text-muted text-tiny">Page {{ $tasks->currentPage() }} / {{ $tasks->lastPage() }}</span>
        </div>
        <div class="card-body p-0">
            @if ($tasks->isEmpty())
                <x-empty-state icon="bi-list-task" title="Aucune tâche" text="Créez une tâche pour organiser le travail des étudiants.">
                    <a href="{{ route('supervisor.tasks.create') }}" class="btn btn-primary btn-sm">Créer une tâche</a>
                </x-empty-state>
            @else
                <div class="table-responsive table-desktop">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>Tâche</th><th>Matière</th><th>Priorité</th><th>Avancement</th><th>Échéance</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($tasks as $task)
                                @php
                                    $isOverdue = $task->isOverdue() && $task->completed_assignments_count < $task->assignments_count;
                                @endphp
                                <tr class="hover-row">
                                    <td>
                                        <a href="{{ route('supervisor.tasks.show', $task) }}" class="fw-semibold text-reset">{{ Str::limit($task->title, 50) }}</a>
                                        <div class="text-muted text-tiny">
                                            @if ($task->exercise)
                                                <i class="bi bi-journal-check me-1"></i>Liée à l’exercice
                                            @elseif ($task->quiz)
                                                <i class="bi bi-patch-question me-1"></i>Liée au quiz
                                            @else
                                                Tâche autonome
                                            @endif
                                        </div>
                                    </td>
                                    <td class="small">{{ $task->subject?->name }}</td>
                                    <td><span class="badge {{ $task->priority->badgeClass() }}">{{ $task->priority->label() }}</span></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2" style="min-width:120px">
                                            <div class="progress flex-grow-1">
                                                <div class="progress-bar {{ $isOverdue ? 'bg-danger' : 'bg-success' }}" style="width: {{ $task->assignments_count > 0 ? round(($task->completed_assignments_count / $task->assignments_count) * 100) : 0 }}%"></div>
                                            </div>
                                            <span class="text-tiny mono-num">{{ $task->completed_assignments_count }}/{{ $task->assignments_count }}</span>
                                        </div>
                                    </td>
                                    <td class="text-tiny {{ $isOverdue ? 'text-danger fw-semibold' : 'text-muted' }}">
                                        {{ $task->due_at?->format('d/m/Y H:i') ?? 'Aucune' }}
                                        @if ($isOverdue)<div>En retard</div>@endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="{{ route('supervisor.tasks.show', $task) }}" class="btn btn-sm btn-outline-primary">Suivre</a>
                                            <a href="{{ route('supervisor.tasks.edit', $task) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mobile-list p-2">
                    @foreach ($tasks as $task)
                        @php
                            $isOverdue = $task->isOverdue() && $task->completed_assignments_count < $task->assignments_count;
                        @endphp
                        <div class="mobile-list-item">
                            <div class="d-flex justify-content-between gap-2">
                                <a href="{{ route('supervisor.tasks.show', $task) }}" class="fw-semibold small text-reset">{{ Str::limit($task->title, 60) }}</a>
                                <span class="badge {{ $task->priority->badgeClass() }}">{{ $task->priority->label() }}</span>
                            </div>
                            <div class="meta-line mt-1">
                                <span>{{ $task->subject?->name }}</span>
                                <span>{{ $task->completed_assignments_count }}/{{ $task->assignments_count }} terminée(s)</span>
                                @if ($task->due_at)
                                    <span class="{{ $isOverdue ? 'text-danger' : '' }}"><i class="bi bi-clock me-1"></i>{{ $task->due_at->format('d/m/Y H:i') }}</span>
                                @endif
                            </div>
                            <div class="d-flex gap-2 mt-2">
                                <a href="{{ route('supervisor.tasks.show', $task) }}" class="btn btn-sm btn-outline-primary flex-grow-1">Suivre</a>
                                <a href="{{ route('supervisor.tasks.edit', $task) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="p-3">{{ $tasks->links() }}</div>
            @endif
        </div>
    </div>
@endsection
