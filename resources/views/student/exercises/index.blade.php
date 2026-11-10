@extends('layouts.app')

@section('title', 'Mes exercices')
@section('topbar-title', 'Mes exercices')

@section('content')
    <x-page-header title="Mes exercices" subtitle="Consultez les consignes et envoyez vos réponses." />

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('student.exercises.index') }}" class="row g-2 align-items-end">
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
                    <label class="form-label" for="state">État</label>
                    <select id="state" name="state" class="form-select" data-auto-submit>
                        <option value="">Tous</option>
                        <option value="todo" @selected($state === 'todo')>À rendre</option>
                        <option value="done" @selected($state === 'done')>Déjà rendus</option>
                    </select>
                </div>
                <div class="col-5 col-md-3">
                    <button class="btn btn-outline-primary w-100" type="submit">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    @if ($exercises->isEmpty())
        <div class="card">
            <x-empty-state icon="bi-journal" title="Aucun exercice" text="Aucun exercice ne correspond à ce filtre pour le moment." />
        </div>
    @else
        <div class="row g-3">
            @foreach ($exercises as $exercise)
                @php
                    $submission = $exercise->submissions->first();
                    $grade = $submission?->latestGrade;
                @endphp
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                <div>
                                    <div class="text-muted text-tiny">{{ $exercise->theme?->subject?->name }} — {{ $exercise->theme?->name }}</div>
                                    <h2 class="h6 mb-0 mt-1">{{ $exercise->title }}</h2>
                                </div>
                                @if ($submission)
                                    <x-status-badge :status="$submission->status" />
                                @else
                                    <span class="badge text-bg-warning">À rendre</span>
                                @endif
                            </div>

                            <div class="meta-line my-2">
                                <span><i class="bi bi-journal-text me-1"></i>{{ $exercise->type->shortLabel() }}</span>
                                <span><i class="bi bi-sliders me-1"></i>{{ $exercise->max_points }} pts</span>
                                @if ($exercise->due_at)
                                    <span class="{{ $exercise->isOverdue() && ! $submission ? 'text-danger fw-semibold' : '' }}">
                                        <i class="bi bi-clock me-1"></i>{{ $exercise->due_at->format('d/m/Y H:i') }}
                                    </span>
                                @endif
                            </div>

                            @if ($grade)
                                <div class="alert alert-light border py-2 small mb-2">
                                    Note : <strong>{{ $grade->score }} / {{ $grade->max_points }}</strong>
                                    ({{ number_format($grade->convertedTo20(), 2, ',', ' ') }}/20)
                                </div>
                            @elseif ($submission)
                                <div class="text-muted text-tiny mb-2">Version {{ $submission->version }} envoyée le {{ $submission->submitted_at->format('d/m/Y H:i') }}.</div>
                            @endif

                            <div class="mt-auto d-flex gap-2">
                                <a href="{{ route('student.exercises.show', $exercise) }}" class="btn btn-outline-primary btn-sm flex-grow-1">
                                    {{ $submission ? 'Voir le détail' : 'Faire l’exercice' }}
                                </a>
                                @if ($submission)
                                    <a href="{{ route('student.submissions.show', $submission) }}" class="btn btn-outline-secondary btn-sm" aria-label="Ma soumission"><i class="bi bi-send-check"></i></a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-3">{{ $exercises->links() }}</div>
    @endif
@endsection
