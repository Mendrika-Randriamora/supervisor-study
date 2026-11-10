@extends('layouts.app')

@section('title', 'Résultat du quiz')
@section('topbar-title', 'Résultat du quiz')

@section('content')
    @php
        $bestAttempt = $attempts->sortByDesc('score')->first();
        $isBest = $bestAttempt && $bestAttempt->id === $attempt->id;
    @endphp

    <div class="page-header">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <h1 class="page-title mb-0">{{ $quiz->title }}</h1>
                <span class="badge text-bg-light">Tentative n°{{ $attempt->attempt_number }}</span>
                @if ($isBest)<span class="badge text-bg-success">Meilleure tentative</span>@endif
            </div>
            <p class="page-subtitle mb-0">{{ $quiz->theme?->subject?->name }} — {{ $quiz->theme?->name }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('student.quizzes.show', $quiz) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour au quiz</a>
            <a href="{{ route('student.results.show', $quiz) }}" class="btn btn-outline-primary btn-sm">Toutes mes tentatives</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><x-stat-card label="Note" :value="$attempt->score.' / '.$attempt->max_points" icon="bi-award" :tone="$attempt->convertedTo20() >= 10 ? 'success' : 'danger'" /></div>
        <div class="col-6 col-md-3"><x-stat-card label="Sur 20" :value="number_format($attempt->convertedTo20(), 2, ',', ' ')" icon="bi-graph-up" :tone="$attempt->convertedTo20() >= 10 ? 'success' : 'danger'" /></div>
        <div class="col-6 col-md-3"><x-stat-card label="Bonnes réponses" :value="$attempt->correct_count" icon="bi-check2-circle" tone="success" /></div>
        <div class="col-6 col-md-3"><x-stat-card label="Erreurs" :value="$attempt->incorrect_count" icon="bi-x-circle" :tone="$attempt->incorrect_count > 0 ? 'danger' : 'success'" /></div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header">Correction détaillée</div>
                <div class="card-body">
                    @foreach ($attempt->answers as $index => $answer)
                        @php
                            $question = $answer->question;
                            $selectedIds = $answer->selectedOptions->pluck('id')->all();
                        @endphp
                        <div class="quiz-question">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <div class="small text-muted">Question {{ $index + 1 }}</div>
                                <div class="d-flex gap-1">
                                    <span class="badge {{ $answer->is_correct ? 'text-bg-success' : 'text-bg-danger' }}">{{ $answer->points_awarded }} / {{ $question?->points }} pt(s)</span>
                                    <span class="badge text-bg-light">{{ $question?->type?->label() }}</span>
                                </div>
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
                                        <span class="text-tiny text-muted">
                                            @if ($selected)
                                                Votre choix
                                            @endif
                                        </span>
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

        <div class="col-12 col-xl-4">
            <div class="card mb-3">
                <div class="card-header">Détails de la tentative</div>
                <div class="card-body">
                    <dl class="detail-list">
                        <div class="detail-row"><dt>Envoyée le</dt><dd>{{ $attempt->submitted_at->format('d/m/Y H:i') }}</dd></div>
                        @if ($attempt->duration_seconds)
                            <div class="detail-row"><dt>Durée</dt><dd>{{ floor($attempt->duration_seconds / 60) }} min {{ $attempt->duration_seconds % 60 }} s</dd></div>
                        @endif
                        <div class="detail-row"><dt>Bonnes réponses</dt><dd>{{ $attempt->correct_count }}</dd></div>
                        <div class="detail-row"><dt>Erreurs</dt><dd>{{ $attempt->incorrect_count }}</dd></div>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Toutes mes tentatives</div>
                <div class="card-body p-0">
                    @foreach ($attempts->sortByDesc('submitted_at') as $item)
                        <a href="{{ route('student.quizzes.result', ['quiz' => $quiz, 'attempt' => $item->id]) }}" class="d-flex justify-content-between gap-2 px-3 py-2 text-reset {{ ! $loop->last ? 'border-bottom' : '' }} {{ $item->id === $attempt->id ? 'bg-primary-soft' : '' }}">
                            <div>
                                <div class="small fw-semibold">Tentative n°{{ $item->attempt_number }}</div>
                                <div class="text-muted text-tiny">{{ $item->submitted_at->format('d/m/Y H:i') }}</div>
                            </div>
                            <span class="small fw-semibold mono-num">{{ $item->score }} / {{ $item->max_points }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
