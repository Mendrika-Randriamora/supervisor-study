@extends('layouts.app')

@section('title', $exercise->title)
@section('topbar-title', 'Détail de l’exercice')

@section('content')
    @php
        $pending = $exercise->submissions()->where('status', \App\Enums\SubmissionStatus::Submitted)->count();
        $graded = $exercise->submissions()->where('status', \App\Enums\SubmissionStatus::Graded)->count();
        $totalSubmissions = $exercise->submissions()->count();
    @endphp

    <div class="page-header">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <h1 class="page-title mb-0">{{ $exercise->title }}</h1>
                <x-status-badge :status="$exercise->status" />
                <span class="badge text-bg-light">{{ $exercise->type->shortLabel() }}</span>
            </div>
            <p class="page-subtitle mb-0">{{ $exercise->theme?->subject?->name }} — {{ $exercise->theme?->name }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('supervisor.exercises.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
            <a href="{{ route('supervisor.exercises.edit', $exercise) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Modifier</a>
            @if ($exercise->status !== \App\Enums\ExerciseStatus::Published)
                <form method="POST" action="{{ route('supervisor.exercises.publish', $exercise) }}">
                    @csrf @method('patch')
                    <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-send me-1"></i>Publier</button>
                </form>
            @else
                <form method="POST" action="{{ route('supervisor.exercises.archive', $exercise) }}">
                    @csrf @method('patch')
                    <button class="btn btn-outline-secondary btn-sm" type="submit" data-confirm="Archiver cet exercice ?">Archiver</button>
                </form>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <x-stat-card label="Soumissions" :value="$totalSubmissions" icon="bi-inbox" hint="toutes versions" />
        </div>
        <div class="col-6 col-md-3">
            <x-stat-card label="À corriger" :value="$pending" icon="bi-hourglass-split" :tone="$pending > 0 ? 'warning' : 'success'" />
        </div>
        <div class="col-6 col-md-3">
            <x-stat-card label="Corrigées" :value="$graded" icon="bi-check2-circle" tone="success" />
        </div>
        <div class="col-6 col-md-3">
            <x-stat-card label="Barème" :value="rtrim(rtrim(number_format((float) $exercise->max_points, 2, ',', ' '), '0'), ',').' pts'" icon="bi-sliders" hint="converti sur 20" />
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-4">
            <div class="card mb-3">
                <div class="card-header">Consignes</div>
                <div class="card-body">
                    <p class="small mb-3" style="white-space: pre-line">{{ $exercise->description ?: 'Aucune consigne complémentaire.' }}</p>
                    <dl class="detail-list">
                        <div class="detail-row"><dt>Type</dt><dd>{{ $exercise->type->label() }}</dd></div>
                        <div class="detail-row"><dt>Barème</dt><dd>{{ $exercise->max_points }} points</dd></div>
                        <div class="detail-row"><dt>Publication</dt><dd>{{ $exercise->published_at?->format('d/m/Y H:i') ?? 'Non publié' }}</dd></div>
                        <div class="detail-row"><dt>Échéance</dt><dd>{{ $exercise->due_at?->format('d/m/Y H:i') ?? 'Aucune' }}</dd></div>
                        <div class="detail-row"><dt>Créé par</dt><dd>{{ $exercise->creator?->name ?? '—' }}</dd></div>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Document PDF</div>
                <div class="card-body">
                    @forelse ($exercise->attachments as $attachment)
                        <a href="{{ route('files.exercise', $attachment) }}" target="_blank" class="d-flex align-items-center gap-2 py-1 text-reset">
                            <i class="bi bi-file-earmark-pdf text-danger fs-5"></i>
                            <span class="small text-truncate">{{ $attachment->original_name }}</span>
                            <span class="text-muted text-tiny ms-auto">{{ number_format($attachment->size / 1024, 0) }} Ko</span>
                        </a>
                    @empty
                        <p class="small text-muted mb-0">Aucun document joint.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Soumissions des étudiants</span>
                    <a href="{{ route('supervisor.submissions.index', ['exercise' => $exercise->id]) }}" class="small">Vue filtrée</a>
                </div>
                <div class="card-body p-0">
                    @if ($submissions->isEmpty())
                        <x-empty-state icon="bi-inbox" title="Aucune soumission" text="Les copies apparaîtront ici dès que les étudiants auront rendu l’exercice." />
                    @else
                        <div class="table-responsive table-desktop">
                            <table class="table table-hover">
                                <thead>
                                    <tr><th>Étudiant</th><th>Version</th><th>Envoyé</th><th>Statut</th><th>Note</th><th></th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($submissions as $submission)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold small">{{ $submission->student?->name }}</div>
                                                @if ($submission->is_late)<span class="badge text-bg-warning">En retard</span>@endif
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
                                    <div class="meta-line mt-1">
                                        <span>Version {{ $submission->version }}</span>
                                        <span>{{ $submission->submitted_at->format('d/m/Y H:i') }}</span>
                                        @if ($submission->is_late)<span class="text-danger">En retard</span>@endif
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
        </div>
    </div>
@endsection
