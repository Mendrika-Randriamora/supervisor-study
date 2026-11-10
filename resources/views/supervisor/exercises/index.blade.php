@extends('layouts.app')

@section('title', 'Exercices')
@section('topbar-title', 'Gestion des exercices')

@section('content')
    <x-page-header title="Exercices" subtitle="Gérez les exercices PDF et textuels, leur publication et leurs corrections.">
        <x-slot:actions>
            <a href="{{ route('supervisor.exercises.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-journal-plus me-1"></i>Nouvel exercice</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="q">Rechercher</label>
                    <input type="search" id="q" name="q" value="{{ $search }}" class="form-control" placeholder="Titre de l’exercice">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="subject">Matière</label>
                    <select id="subject" name="subject" class="form-select" data-auto-submit>
                        <option value="">Toutes</option>
                        @foreach ($subjects as $subjectItem)
                            <option value="{{ $subjectItem->id }}" @selected($selectedSubject == $subjectItem->id)>{{ $subjectItem->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="status">Statut</label>
                    <select id="status" name="status" class="form-select" data-auto-submit>
                        <option value="">Tous</option>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption->value }}" @selected($status === $statusOption->value)>{{ $statusOption->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <button class="btn btn-outline-primary w-100" type="submit">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>{{ $exercises->total() }} exercice(s)</span>
            <span class="text-muted text-tiny">Page {{ $exercises->currentPage() }} / {{ $exercises->lastPage() }}</span>
        </div>
        <div class="card-body p-0">
            @if ($exercises->isEmpty())
                <x-empty-state icon="bi-journal" title="Aucun exercice" text="Créez un exercice PDF ou textuel pour vos étudiants.">
                    <a href="{{ route('supervisor.exercises.create') }}" class="btn btn-primary btn-sm">Créer un exercice</a>
                </x-empty-state>
            @else
                <div class="table-responsive table-desktop">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>Exercice</th><th>Matière / thème</th><th>Type</th><th>Statut</th><th>Rendus</th><th>Échéance</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($exercises as $exercise)
                                <tr class="hover-row">
                                    <td>
                                        <a href="{{ route('supervisor.exercises.show', $exercise) }}" class="fw-semibold text-reset">{{ Str::limit($exercise->title, 48) }}</a>
                                        <div class="text-muted text-tiny">Barème {{ rtrim(rtrim(number_format((float) $exercise->max_points, 2, ',', ' '), '0'), ',') }} pts</div>
                                    </td>
                                    <td>
                                        <div class="small">{{ $exercise->theme?->subject?->name }}</div>
                                        <div class="text-muted text-tiny">{{ $exercise->theme?->name }}</div>
                                    </td>
                                    <td><span class="badge text-bg-light">{{ $exercise->type->shortLabel() }}</span></td>
                                    <td><x-status-badge :status="$exercise->status" /></td>
                                    <td class="mono-num">{{ $exercise->submissions_count }}</td>
                                    <td class="text-muted text-tiny">{{ $exercise->due_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('supervisor.exercises.show', $exercise) }}" class="btn btn-sm btn-outline-primary">Ouvrir</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mobile-list p-2">
                    @foreach ($exercises as $exercise)
                        <div class="mobile-list-item">
                            <div class="d-flex justify-content-between gap-2">
                                <a href="{{ route('supervisor.exercises.show', $exercise) }}" class="fw-semibold small text-reset">{{ Str::limit($exercise->title, 55) }}</a>
                                <x-status-badge :status="$exercise->status" />
                            </div>
                            <div class="meta-line mt-1">
                                <span>{{ $exercise->theme?->subject?->name }}</span>
                                <span>{{ $exercise->type->shortLabel() }}</span>
                                <span>{{ $exercise->submissions_count }} rendu(s)</span>
                            </div>
                            @if ($exercise->due_at)
                                <div class="meta-line mt-1"><span><i class="bi bi-clock me-1"></i>{{ $exercise->due_at->format('d/m/Y H:i') }}</span></div>
                            @endif
                            <a href="{{ route('supervisor.exercises.show', $exercise) }}" class="btn btn-sm btn-outline-primary w-100 mt-2">Ouvrir</a>
                        </div>
                    @endforeach
                </div>
                <div class="p-3">{{ $exercises->links() }}</div>
            @endif
        </div>
    </div>
@endsection
