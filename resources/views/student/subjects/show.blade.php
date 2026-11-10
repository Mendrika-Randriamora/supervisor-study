@extends('layouts.app')

@section('title', $subject->name)
@section('topbar-title', 'Matière')

@section('content')
    @php
        $exercisesByTheme = $exercises->groupBy(fn ($exercise) => $exercise->theme?->name ?? 'Sans thème');
        $quizzesByTheme = $quizzes->groupBy(fn ($quiz) => $quiz->theme?->name ?? 'Sans thème');
    @endphp

    <div class="page-header">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span style="width:12px;height:12px;border-radius:50%;background:{{ $subject->color }}"></span>
                <h1 class="page-title mb-0">{{ $subject->name }}</h1>
                @if ($subject->code)<span class="badge text-bg-light">{{ $subject->code }}</span>@endif
            </div>
            <p class="page-subtitle mb-0">{{ $subject->description ?: 'Aucune description.' }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('student.subjects.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Mes matières</a>
            <a href="{{ route('student.exercises.index', ['subject' => $subject->id]) }}" class="btn btn-primary btn-sm">Exercices</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><x-stat-card label="Exercices" :value="$exercises->count()" icon="bi-journal-check" /></div>
        <div class="col-6 col-md-3"><x-stat-card label="Quiz" :value="$quizzes->count()" icon="bi-patch-question" /></div>
        <div class="col-6 col-md-3"><x-stat-card label="Tâches" :value="$tasks->count()" icon="bi-list-task" /></div>
        <div class="col-6 col-md-3"><x-stat-card label="Thèmes" :value="$subject->themes->count()" icon="bi-diagram-3" /></div>
    </div>

    @if ($subject->themes->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header">Thèmes de la matière</div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($subject->themes as $theme)
                        <span class="badge {{ $theme->isArchived() ? 'text-bg-dark' : 'text-bg-light' }}">{{ $theme->name }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Exercices</span>
                    <a href="{{ route('student.exercises.index', ['subject' => $subject->id]) }}" class="small">Tout voir</a>
                </div>
                <div class="card-body">
                    @forelse ($exercisesByTheme as $themeName => $themeExercises)
                        <div class="mb-3">
                            <div class="nav-label mb-2">{{ $themeName }}</div>
                            @foreach ($themeExercises as $exercise)
                                @php
                                    $submission = $exercise->submissions->first();
                                    $grade = $submission?->latestGrade;
                                @endphp
                                <a href="{{ route('student.exercises.show', $exercise) }}" class="d-flex justify-content-between gap-2 py-2 text-reset {{ ! $loop->last ? 'border-bottom' : '' }}">
                                    <div class="flex-grow-1">
                                        <div class="small fw-semibold">{{ $exercise->title }}</div>
                                        <div class="meta-line">
                                            <span>{{ $exercise->type->shortLabel() }}</span>
                                            @if ($exercise->due_at)
                                                <span class="{{ $exercise->isOverdue() && ! $submission ? 'text-danger' : '' }}"><i class="bi bi-clock me-1"></i>{{ $exercise->due_at->format('d/m/Y H:i') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        @if ($submission)
                                            <x-status-badge :status="$submission->status" />
                                        @else
                                            <span class="badge text-bg-warning">À faire</span>
                                        @endif
                                        @if ($grade)
                                            <div class="text-tiny mono-num mt-1">{{ $grade->score }} / {{ $grade->max_points }}</div>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @empty
                        <x-empty-state icon="bi-journal" title="Aucun exercice" text="Aucun exercice publié pour cette matière." />
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Quiz</span>
                    <a href="{{ route('student.quizzes.index', ['subject' => $subject->id]) }}" class="small">Tout voir</a>
                </div>
                <div class="card-body">
                    @forelse ($quizzesByTheme as $themeName => $themeQuizzes)
                        <div class="mb-3">
                            <div class="nav-label mb-2">{{ $themeName }}</div>
                            @foreach ($themeQuizzes as $quiz)
                                @php $best = $quiz->attempts->sortByDesc('score')->first(); @endphp
                                <a href="{{ route('student.quizzes.show', $quiz) }}" class="d-flex justify-content-between gap-2 py-2 text-reset {{ ! $loop->last ? 'border-bottom' : '' }}">
                                    <div class="flex-grow-1">
                                        <div class="small fw-semibold">{{ $quiz->title }}</div>
                                        <div class="meta-line">
                                            <span>{{ $quiz->questions_count ?? '—' }} question(s)</span>
                                            @if ($quiz->due_at)
                                                <span class="{{ $quiz->isOverdue() && ! $best ? 'text-danger' : '' }}"><i class="bi bi-clock me-1"></i>{{ $quiz->due_at->format('d/m/Y H:i') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        @if ($best)
                                            <span class="badge {{ $best->convertedTo20() >= 10 ? 'text-bg-success' : 'text-bg-danger' }}">{{ number_format($best->convertedTo20(), 1, ',', ' ') }}/20</span>
                                        @else
                                            <span class="badge text-bg-warning">À faire</span>
                                        @endif
                                        <div class="text-muted text-tiny mt-1">{{ $quiz->attempts->count() }} tentative(s)</div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @empty
                        <x-empty-state icon="bi-patch-question" title="Aucun quiz" text="Aucun quiz publié pour cette matière." />
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Mes tâches</span>
                    <a href="{{ route('student.tasks.index', ['subject' => $subject->id]) }}" class="small">Tout voir</a>
                </div>
                <div class="card-body">
                    @forelse ($tasks as $assignment)
                        <a href="{{ route('student.tasks.show', $assignment->task) }}" class="d-flex justify-content-between gap-2 py-2 text-reset {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <div class="flex-grow-1">
                                <div class="small fw-semibold">{{ $assignment->task?->title }}</div>
                                <div class="meta-line">
                                    <span>{{ $assignment->task?->priority?->label() }}</span>
                                    @if ($assignment->task?->due_at)
                                        <span><i class="bi bi-clock me-1"></i>{{ $assignment->task->due_at->format('d/m/Y H:i') }}</span>
                                    @endif
                                </div>
                            </div>
                            <x-status-badge :status="$assignment->effectiveStatus()" />
                        </a>
                    @empty
                        <x-empty-state icon="bi-list-task" title="Aucune tâche" text="Aucune tâche attribuée pour cette matière." />
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
