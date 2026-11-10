@extends('layouts.app')

@section('title', $quiz->title)
@section('topbar-title', 'Quiz')

@section('content')
    @php
        $average = $attempts->isNotEmpty() ? $attempts->avg(fn ($attempt) => $attempt->convertedTo20()) : null;
        $canAttempt = ! $quiz->isOverdue() && $quiz->questions_count > 0;
    @endphp

    <div class="page-header">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <h1 class="page-title mb-0">{{ $quiz->title }}</h1>
                @if ($quiz->isOverdue())<span class="badge text-bg-dark">Échéance dépassée</span>@endif
            </div>
            <p class="page-subtitle mb-0">{{ $quiz->theme?->subject?->name }} — {{ $quiz->theme?->name }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('student.quizzes.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
            @if ($attempts->isNotEmpty())
                <a href="{{ route('student.results.show', $quiz) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-award me-1"></i>Mes résultats</a>
            @endif
        </div>
    </div>

    @if ($canAttempt)
        <div class="alert alert-light border d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex gap-2 align-items-start">
                <i class="bi bi-info-circle text-primary mt-1"></i>
                <div class="small">
                    Vous pouvez passer ce quiz plusieurs fois. La meilleure tentative est retenue pour la moyenne ; toutes les tentatives restent conservées.
                </div>
            </div>
            <form method="POST" action="{{ route('student.quizzes.start', $quiz) }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-play-fill me-1"></i>{{ $attempts->isNotEmpty() ? 'Nouvelle tentative' : 'Commencer le quiz' }}</button>
            </form>
        </div>
    @elseif ($quiz->isOverdue())
        <div class="alert alert-warning d-flex gap-2 align-items-start">
            <i class="bi bi-alarm mt-1"></i>
            <div>La date limite de ce quiz est dépassée. Vous pouvez consulter vos résultats précédents, mais une nouvelle tentative n’est plus possible.</div>
        </div>
    @elseif ($quiz->questions_count === 0)
        <div class="alert alert-warning">Ce quiz ne contient encore aucune question.</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><x-stat-card label="Questions" :value="$quiz->questions_count" icon="bi-question-circle" /></div>
        <div class="col-6 col-md-3"><x-stat-card label="Barème" :value="$quiz->total_points.' pts'" icon="bi-sliders" hint="converti sur 20" /></div>
        <div class="col-6 col-md-3"><x-stat-card label="Tentatives" :value="$attempts->count()" icon="bi-arrow-repeat" /></div>
        <div class="col-6 col-md-3"><x-stat-card label="Meilleure note" :value="$bestAttempt ? number_format($bestAttempt->convertedTo20(), 2, ',', ' ').' /20' : '—'" icon="bi-trophy" :tone="$bestAttempt && $bestAttempt->convertedTo20() >= 10 ? 'success' : 'primary'" /></div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-5">
            <div class="card mb-3">
                <div class="card-header">Consignes</div>
                <div class="card-body">
                    <p class="small mb-3" style="white-space: pre-line">{{ $quiz->description ?: 'Aucune consigne complémentaire.' }}</p>
                    <dl class="detail-list">
                        <div class="detail-row"><dt>Matière</dt><dd>{{ $quiz->theme?->subject?->name ?? '—' }}</dd></div>
                        <div class="detail-row"><dt>Thème</dt><dd>{{ $quiz->theme?->name ?? '—' }}</dd></div>
                        <div class="detail-row"><dt>Barème total</dt><dd>{{ $quiz->total_points }} points</dd></div>
                        <div class="detail-row"><dt>Temps conseillé</dt><dd>{{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes.' minutes' : 'Libre' }}</dd></div>
                        <div class="detail-row"><dt>Échéance</dt><dd class="{{ $quiz->isOverdue() ? 'text-danger' : '' }}">{{ $quiz->due_at?->format('d/m/Y H:i') ?? 'Aucune' }}</dd></div>
                    </dl>
                </div>
            </div>

            @if ($latestAttempt)
                <div class="card">
                    <div class="card-header">Dernière tentative</div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-baseline mb-1">
                            <span class="stat-value">{{ $latestAttempt->score }} / {{ $latestAttempt->max_points }}</span>
                            <span class="badge {{ $latestAttempt->convertedTo20() >= 10 ? 'text-bg-success' : 'text-bg-danger' }}">{{ number_format($latestAttempt->convertedTo20(), 2, ',', ' ') }}/20</span>
                        </div>
                        <div class="meta-line">
                            <span>Tentative n°{{ $latestAttempt->attempt_number }}</span>
                            <span>{{ $latestAttempt->submitted_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="progress progress-thin mt-2">
                            <div class="progress-bar" style="width: {{ $latestAttempt->max_points > 0 ? round(($latestAttempt->score / $latestAttempt->max_points) * 100) : 0 }}%"></div>
                        </div>
                        <a href="{{ route('student.quizzes.result', ['quiz' => $quiz, 'attempt' => $latestAttempt->id]) }}" class="btn btn-outline-primary btn-sm w-100 mt-3">Voir le détail</a>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-12 col-xl-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Mes tentatives</span>
                    @if ($average !== null)
                        <span class="text-muted text-tiny">Moyenne : {{ number_format($average, 2, ',', ' ') }}/20</span>
                    @endif
                </div>
                <div class="card-body p-0">
                    @forelse ($attempts->sortByDesc('submitted_at') as $attempt)
                        <div class="d-flex flex-wrap justify-content-between gap-2 px-3 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <div>
                                <div class="small fw-semibold">
                                    Tentative n°{{ $attempt->attempt_number }}
                                    @if ($bestAttempt && $attempt->id === $bestAttempt->id)
                                        <span class="badge text-bg-success ms-1">Meilleure</span>
                                    @endif
                                </div>
                                <div class="meta-line">
                                    <span>{{ $attempt->submitted_at->format('d/m/Y H:i') }}</span>
                                    <span>{{ $attempt->correct_count }} bonne(s) / {{ $attempt->incorrect_count }} mauvaise(s)</span>
                                    @if ($attempt->duration_seconds)
                                        <span>Durée : {{ floor($attempt->duration_seconds / 60) }} min {{ $attempt->duration_seconds % 60 }} s</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="small fw-semibold mono-num">{{ $attempt->score }} / {{ $attempt->max_points }}</div>
                                <a href="{{ route('student.quizzes.result', ['quiz' => $quiz, 'attempt' => $attempt->id]) }}" class="small">Détail <i class="bi bi-chevron-right"></i></a>
                            </div>
                        </div>
                    @empty
                        <x-empty-state icon="bi-patch-question" title="Aucune tentative" text="Vous n’avez pas encore passé ce quiz." />
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
