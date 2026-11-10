@extends('layouts.app')

@section('title', 'Soumissions')
@section('topbar-title', 'Soumissions à corriger')

@section('content')
    <x-page-header title="Soumissions" subtitle="Consultez les copies, annotez les images et attribuez les notes." />

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label" for="exercise">Exercice</label>
                    <select id="exercise" name="exercise" class="form-select" data-auto-submit>
                        <option value="">Tous les exercices</option>
                        @foreach ($exercises as $exercise)
                            <option value="{{ $exercise->id }}" @selected($selectedExercise == $exercise->id)>{{ Str::limit($exercise->title, 60) }} — {{ $exercise->theme?->subject?->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-8 col-md-4">
                    <label class="form-label" for="status">Statut</label>
                    <select id="status" name="status" class="form-select" data-auto-submit>
                        <option value="">Tous</option>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption->value }}" @selected($status === $statusOption->value)>{{ $statusOption->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4 col-md-3">
                    <button class="btn btn-outline-primary w-100" type="submit">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>{{ $submissions->total() }} soumission(s)</span>
            <span class="text-muted text-tiny">Page {{ $submissions->currentPage() }} / {{ $submissions->lastPage() }}</span>
        </div>
        <div class="card-body p-0">
            @if ($submissions->isEmpty())
                <x-empty-state icon="bi-check2-circle" title="Aucune soumission" text="Aucune copie ne correspond à ces filtres." />
            @else
                <div class="table-responsive table-desktop">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>Étudiant</th><th>Exercice</th><th>Version</th><th>Envoyé</th><th>Statut</th><th>Note</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($submissions as $submission)
                                <tr class="hover-row">
                                    <td>
                                        <div class="fw-semibold small">{{ $submission->student?->name }}</div>
                                        <div class="text-muted text-tiny">{{ $submission->student?->email }}</div>
                                    </td>
                                    <td>
                                        <div class="small">{{ Str::limit($submission->exercise?->title, 42) }}</div>
                                        <div class="text-muted text-tiny">{{ $submission->exercise?->theme?->subject?->name }}</div>
                                    </td>
                                    <td class="mono-num">v{{ $submission->version }}</td>
                                    <td class="text-muted text-tiny">{{ $submission->submitted_at->format('d/m/Y H:i') }}</td>
                                    <td><x-status-badge :status="$submission->status" /></td>
                                    <td class="mono-num">
                                        @if ($submission->latestGrade)
                                            {{ $submission->latestGrade->score }} / {{ $submission->latestGrade->max_points }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end"><a href="{{ route('supervisor.submissions.show', $submission) }}" class="btn btn-sm btn-outline-primary">Corriger</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mobile-list p-2">
                    @foreach ($submissions as $submission)
                        <div class="mobile-list-item">
                            <div class="d-flex justify-content-between gap-2">
                                <span class="fw-semibold small">{{ $submission->student?->name }}</span>
                                <x-status-badge :status="$submission->status" />
                            </div>
                            <div class="small mt-1">{{ Str::limit($submission->exercise?->title, 55) }}</div>
                            <div class="meta-line mt-1">
                                <span>{{ $submission->exercise?->theme?->subject?->name }}</span>
                                <span>v{{ $submission->version }}</span>
                                <span>{{ $submission->submitted_at->format('d/m H:i') }}</span>
                            </div>
                            @if ($submission->latestGrade)
                                <div class="small mt-1">Note : <strong>{{ $submission->latestGrade->score }} / {{ $submission->latestGrade->max_points }}</strong></div>
                            @endif
                            <a href="{{ route('supervisor.submissions.show', $submission) }}" class="btn btn-sm btn-outline-primary w-100 mt-2">Corriger</a>
                        </div>
                    @endforeach
                </div>
                <div class="p-3">{{ $submissions->links() }}</div>
            @endif
        </div>
    </div>
@endsection
