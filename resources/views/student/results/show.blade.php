@extends('layouts.app')

@section('title', 'Résultats — '.$quiz->title)
@section('topbar-title', 'Mes résultats')

@section('content')
    @php
        $best = $attempts->sortByDesc('score')->first();
        $average = $attempts->isNotEmpty() ? $attempts->avg(fn ($attempt) => $attempt->convertedTo20()) : null;
        $last = $attempts->sortByDesc('submitted_at')->first();
    @endphp

    <div class="page-header">
        <div>
            <h1 class="page-title mb-1">{{ $quiz->title }}</h1>
            <p class="page-subtitle mb-0">{{ $quiz->theme?->subject?->name }} — {{ $quiz->theme?->name }} · {{ $attempts->count() }} tentative(s)</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('student.results.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Mes résultats</a>
            <a href="{{ route('student.quizzes.show', $quiz) }}" class="btn btn-outline-primary btn-sm">Retour au quiz</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><x-stat-card label="Meilleure note" :value="$best ? number_format($best->convertedTo20(), 2, ',', ' ').' /20' : '—'" icon="bi-trophy" tone="success" /></div>
        <div class="col-6 col-md-3"><x-stat-card label="Dernière note" :value="$last ? number_format($last->convertedTo20(), 2, ',', ' ').' /20' : '—'" icon="bi-clock-history" /></div>
        <div class="col-6 col-md-3"><x-stat-card label="Moyenne des tentatives" :value="$average !== null ? number_format($average, 2, ',', ' ').' /20' : '—'" icon="bi-graph-up" /></div>
        <div class="col-6 col-md-3"><x-stat-card label="Tentatives" :value="$attempts->count()" icon="bi-arrow-repeat" hint="toutes conservées" /></div>
    </div>

    <div class="alert alert-light border small">
        <i class="bi bi-info-circle text-primary me-1"></i>
        La moyenne générale utilise la <strong>meilleure</strong> tentative de ce quiz. Toutes les tentatives restent consultables ci-dessous.
    </div>

    <div class="accordion" id="attemptsAccordion">
        @foreach ($attempts->sortByDesc('submitted_at') as $attempt)
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading-{{ $attempt->id }}">
                    <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#attempt-{{ $attempt->id }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="attempt-{{ $attempt->id }}">
                        <span class="d-flex flex-wrap align-items-center gap-2 w-100 pe-2">
                            <span class="fw-semibold">Tentative n°{{ $attempt->attempt_number }}</span>
                            <span class="text-muted text-tiny">{{ $attempt->submitted_at->format('d/m/Y H:i') }}</span>
                            @if ($best && $best->id === $attempt->id)<span class="badge text-bg-success">Meilleure</span>@endif
                            <span class="ms-auto badge {{ $attempt->convertedTo20() >= 10 ? 'text-bg-success' : 'text-bg-danger' }}">{{ $attempt->score }} / {{ $attempt->max_points }} — {{ number_format($attempt->convertedTo20(), 2, ',', ' ') }}/20</span>
                        </span>
                    </button>
                </h2>
                <div id="attempt-{{ $attempt->id }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" aria-labelledby="heading-{{ $attempt->id }}" data-bs-parent="#attemptsAccordion">
                    <div class="accordion-body">
                        @foreach ($attempt->answers as $index => $answer)
                            @php
                                $question = $answer->question;
                                $selectedIds = $answer->selectedOptions->pluck('id')->all();
                            @endphp
                            <div class="quiz-question">
                                <div class="d-flex flex-wrap justify-content-between gap-2">
                                    <div class="small text-muted">Question {{ $index + 1 }}</div>
                                    <span class="badge {{ $answer->is_correct ? 'text-bg-success' : 'text-bg-danger' }}">{{ $answer->points_awarded }} / {{ $question?->points }} pt(s)</span>
                                </div>
                                <div class="fw-semibold mt-1 mb-2">{{ $question?->question_text }}</div>
                                <div class="d-grid gap-1">
                                    @foreach ($question?->options ?? [] as $option)
                                        @php
                                            $selected = in_array($option->id, $selectedIds, true);
                                            $stateClass = $option->is_correct
                                                ? 'border-success bg-success-subtle'
                                                : ($selected ? 'border-danger bg-danger-subtle' : '');
                                        @endphp
                                        <div class="quiz-option {{ $stateClass }}" style="cursor: default">
                                            <span class="mt-1">
                                                @if ($option->is_correct)
                                                    <i class="bi bi-check-circle-fill text-success"></i>
                                                @elseif ($selected)
                                                    <i class="bi bi-x-circle-fill text-danger"></i>
                                                @else
                                                    <i class="bi bi-circle text-muted"></i>
                                                @endif
                                            </span>
                                            <span class="small flex-grow-1">{{ $option->option_text }}</span>
                                            @if ($selected)<span class="text-tiny text-muted">Votre choix</span>@endif
                                        </div>
                                    @endforeach
                                </div>
                                @if ($question?->explanation)
                                    <div class="alert alert-light border mt-2 mb-0 py-2 small">
                                        <i class="bi bi-lightbulb me-1"></i>{{ $question->explanation }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
