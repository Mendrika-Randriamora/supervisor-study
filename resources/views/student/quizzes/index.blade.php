@extends('layouts.app')

@section('title', 'Mes quiz')
@section('topbar-title', 'Mes quiz')

@section('content')
    <x-page-header title="Mes quiz" subtitle="Répondez aux quiz et consultez vos tentatives." />

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('student.quizzes.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-6">
                    <label class="form-label" for="subject">Matière</label>
                    <select id="subject" name="subject" class="form-select" data-auto-submit>
                        <option value="">Toutes les matières</option>
                        @foreach ($subjects as $subjectItem)
                            <option value="{{ $subjectItem->id }}" @selected($selectedSubject == $subjectItem->id)>{{ $subjectItem->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <button class="btn btn-outline-primary w-100" type="submit">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    @if ($quizzes->isEmpty())
        <div class="card">
            <x-empty-state icon="bi-patch-question" title="Aucun quiz" text="Aucun quiz publié ne correspond à ce filtre." />
        </div>
    @else
        <div class="row g-3">
            @foreach ($quizzes as $quiz)
                @php
                    $attempts = $quiz->attempts;
                    $best = $attempts->sortByDesc('score')->first();
                    $latest = $attempts->sortByDesc('submitted_at')->first();
                    $average = $attempts->isNotEmpty() ? $attempts->avg(fn ($attempt) => $attempt->convertedTo20()) : null;
                @endphp
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                <div>
                                    <div class="text-muted text-tiny">{{ $quiz->theme?->subject?->name }} — {{ $quiz->theme?->name }}</div>
                                    <h2 class="h6 mb-0 mt-1">{{ $quiz->title }}</h2>
                                </div>
                                @if ($attempts->isNotEmpty())
                                    <span class="badge {{ $best->convertedTo20() >= 10 ? 'text-bg-success' : 'text-bg-danger' }}">{{ number_format($best->convertedTo20(), 1, ',', ' ') }}/20</span>
                                @else
                                    <span class="badge text-bg-warning">À faire</span>
                                @endif
                            </div>

                            <div class="meta-line my-2">
                                <span><i class="bi bi-question-circle me-1"></i>{{ $quiz->questions_count }} question(s)</span>
                                <span><i class="bi bi-sliders me-1"></i>{{ $quiz->total_points }} pts</span>
                                @if ($quiz->time_limit_minutes)
                                    <span><i class="bi bi-stopwatch me-1"></i>{{ $quiz->time_limit_minutes }} min</span>
                                @endif
                                @if ($quiz->due_at)
                                    <span class="{{ $quiz->isOverdue() && $attempts->isEmpty() ? 'text-danger fw-semibold' : '' }}"><i class="bi bi-clock me-1"></i>{{ $quiz->due_at->format('d/m/Y H:i') }}</span>
                                @endif
                            </div>

                            @if ($attempts->isNotEmpty())
                                <div class="soft-panel py-2 mb-2">
                                    <div class="d-flex justify-content-between small">
                                        <span>Meilleure : <strong>{{ number_format($best->convertedTo20(), 2, ',', ' ') }}/20</strong></span>
                                        <span class="text-muted">{{ $attempts->count() }} tentative(s)</span>
                                    </div>
                                    <div class="d-flex justify-content-between text-tiny text-muted mt-1">
                                        <span>Dernière : {{ number_format($latest->convertedTo20(), 2, ',', ' ') }}/20</span>
                                        <span>Moyenne : {{ number_format($average, 2, ',', ' ') }}/20</span>
                                    </div>
                                </div>
                            @else
                                <p class="small text-muted mb-2">{{ $quiz->description ?: 'Aucune description.' }}</p>
                            @endif

                            <div class="mt-auto d-flex gap-2">
                                <a href="{{ route('student.quizzes.show', $quiz) }}" class="btn btn-outline-primary btn-sm flex-grow-1">Détails</a>
                                @if ($attempts->isNotEmpty())
                                    <a href="{{ route('student.results.show', $quiz) }}" class="btn btn-outline-secondary btn-sm" aria-label="Mes résultats"><i class="bi bi-award"></i></a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-3">{{ $quizzes->links() }}</div>
    @endif
@endsection
