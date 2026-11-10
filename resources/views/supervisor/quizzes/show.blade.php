@extends('layouts.app')

@section('title', $quiz->title)
@section('topbar-title', 'Détail du quiz')

@section('content')
    @php
        $editable = $quiz->status !== \App\Enums\QuizStatus::Published && $attemptCount === 0;
        $attemptRows = $attemptsByStudent->map(function ($attempts) {
            $best = $attempts->sortByDesc('score')->first();
            $last = $attempts->sortByDesc('submitted_at')->first();
            $average = $attempts->avg(fn ($attempt) => $attempt->convertedTo20());
            return ['student' => $best->student, 'attempts' => $attempts, 'best' => $best, 'last' => $last, 'average' => $average];
        })->sortByDesc(fn ($row) => $row['average']);
    @endphp

    <div class="page-header">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <h1 class="page-title mb-0">{{ $quiz->title }}</h1>
                <x-status-badge :status="$quiz->status" />
                <span class="badge text-bg-light">{{ $quiz->questions->count() }} question(s)</span>
                <span class="badge text-bg-light">{{ $quiz->total_points }} points</span>
            </div>
            <p class="page-subtitle mb-0">{{ $quiz->theme?->subject?->name }} — {{ $quiz->theme?->name }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('supervisor.quizzes.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
            <a href="{{ route('supervisor.quizzes.edit', $quiz) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Modifier</a>
            @if ($quiz->status !== \App\Enums\QuizStatus::Published)
                <form method="POST" action="{{ route('supervisor.quizzes.publish', $quiz) }}">
                    @csrf @method('patch')
                    <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-send me-1"></i>Publier</button>
                </form>
            @else
                <form method="POST" action="{{ route('supervisor.quizzes.archive', $quiz) }}">
                    @csrf @method('patch')
                    <button class="btn btn-outline-secondary btn-sm" type="submit" data-confirm="Archiver ce quiz ?">Archiver</button>
                </form>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><x-stat-card label="Questions" :value="$quiz->questions->count()" icon="bi-question-circle" /></div>
        <div class="col-6 col-md-3"><x-stat-card label="Barème total" :value="$quiz->total_points.' pts'" icon="bi-sliders" /></div>
        <div class="col-6 col-md-3"><x-stat-card label="Tentatives" :value="$attemptCount" icon="bi-arrow-repeat" hint="{{ $attemptsByStudent->count() }} étudiant(s)" /></div>
        <div class="col-6 col-md-3"><x-stat-card label="Échéance" :value="$quiz->due_at?->format('d/m') ?? '—'" icon="bi-clock" :hint="$quiz->due_at?->format('H:i')" /></div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-7">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Questions</span>
                    @if (! $editable)
                        <span class="badge text-bg-secondary">Lecture seule</span>
                    @endif
                </div>
                <div class="card-body">
                    @if ($quiz->description)
                        <p class="small text-muted">{{ $quiz->description }}</p>
                    @endif

                    @forelse ($quiz->questions as $question)
                        <div class="quiz-question">
                            <div class="d-flex justify-content-between gap-2">
                                <div class="small text-muted">Question {{ $loop->iteration }}</div>
                                <div class="d-flex gap-1">
                                    <span class="badge text-bg-light">{{ $question->type->label() }}</span>
                                    <span class="badge text-bg-light">{{ $question->points }} pts</span>
                                </div>
                            </div>
                            <div class="fw-semibold mt-1">{{ $question->question_text }}</div>
                            <ol class="small mt-2 mb-2 ps-3">
                                @foreach ($question->options as $option)
                                    <li class="{{ $option->is_correct ? 'text-success fw-semibold' : '' }}">
                                        {{ $option->option_text }}
                                        @if ($option->is_correct)<i class="bi bi-check-circle-fill ms-1"></i>@endif
                                    </li>
                                @endforeach
                            </ol>
                            @if ($question->explanation)
                                <div class="soft-panel small mb-2"><strong>Explication :</strong> {{ $question->explanation }}</div>
                            @endif

                            @if ($editable)
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#question-edit-{{ $question->id }}"><i class="bi bi-pencil me-1"></i>Modifier</button>
                                    <form method="POST" action="{{ route('supervisor.quizzes.questions.destroy', [$quiz, $question]) }}">
                                        @csrf @method('delete')
                                        <button class="btn btn-sm btn-outline-danger" type="submit" data-confirm="Supprimer cette question ?"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                                <div class="collapse mt-2" id="question-edit-{{ $question->id }}">
                                    <div class="soft-panel">
                                        @include('supervisor.quizzes._question-form', [
                                            'action' => route('supervisor.quizzes.questions.update', [$quiz, $question]),
                                            'method' => 'patch',
                                            'question' => $question,
                                            'submitLabel' => 'Mettre à jour',
                                        ])
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <x-empty-state icon="bi-question-circle" title="Aucune question" text="Ajoutez une première question pour construire le quiz." />
                    @endforelse
                </div>
            </div>

            @if ($editable)
                <div class="card">
                    <div class="card-header">Ajouter une question</div>
                    <div class="card-body">
                        @include('supervisor.quizzes._question-form', [
                            'action' => route('supervisor.quizzes.questions.store', $quiz),
                            'method' => 'post',
                            'question' => null,
                            'submitLabel' => 'Ajouter la question',
                        ])
                    </div>
                </div>
            @endif
        </div>

        <div class="col-12 col-xl-5">
            <div class="card">
                <div class="card-header">Résultats par étudiant</div>
                <div class="card-body p-0">
                    @if ($attemptRows->isEmpty())
                        <x-empty-state icon="bi-people" title="Aucune tentative" text="Les résultats apparaîtront après le passage du quiz." />
                    @else
                        <div class="table-responsive table-desktop">
                            <table class="table">
                                <thead><tr><th>Étudiant</th><th>Tent.</th><th>Meilleure</th><th>Dernière</th><th></th></tr></thead>
                                <tbody>
                                    @foreach ($attemptRows as $row)
                                        <tr>
                                            <td class="small fw-semibold">{{ $row['student']?->name }}</td>
                                            <td class="mono-num">{{ $row['attempts']->count() }}</td>
                                            <td class="mono-num fw-semibold">{{ number_format($row['best']->convertedTo20(), 2, ',', ' ') }}</td>
                                            <td class="mono-num">{{ number_format($row['last']->convertedTo20(), 2, ',', ' ') }}</td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#attempts-{{ $row['student']?->id }}"><i class="bi bi-list-ul"></i></button>
                                            </td>
                                        </tr>
                                        <tr class="collapse" id="attempts-{{ $row['student']?->id }}">
                                            <td colspan="5" class="bg-light">
                                                <div class="small fw-semibold mb-1">Détail des tentatives — moyenne {{ number_format($row['average'], 2, ',', ' ') }}/20</div>
                                                @foreach ($row['attempts']->sortByDesc('submitted_at') as $attempt)
                                                    <div class="d-flex justify-content-between gap-2 py-1 border-bottom">
                                                        <span>N°{{ $attempt->attempt_number }} — {{ $attempt->submitted_at->format('d/m/Y H:i') }}</span>
                                                        <span class="mono-num">{{ $attempt->score }} / {{ $attempt->max_points }} ({{ $attempt->correct_count }} bonnes / {{ $attempt->incorrect_count }} mauvaises)</span>
                                                    </div>
                                                @endforeach
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mobile-list p-2">
                            @foreach ($attemptRows as $row)
                                <div class="mobile-list-item">
                                    <div class="d-flex justify-content-between gap-2">
                                        <div class="fw-semibold small">{{ $row['student']?->name }}</div>
                                        <span class="badge text-bg-light">{{ $row['attempts']->count() }} tentative(s)</span>
                                    </div>
                                    <div class="meta-line mt-1">
                                        <span>Meilleure : {{ number_format($row['best']->convertedTo20(), 2, ',', ' ') }}/20</span>
                                        <span>Dernière : {{ number_format($row['last']->convertedTo20(), 2, ',', ' ') }}/20</span>
                                        <span>Moyenne : {{ number_format($row['average'], 2, ',', ' ') }}/20</span>
                                    </div>
                                    <div class="mt-2">
                                        @foreach ($row['attempts']->sortByDesc('submitted_at') as $attempt)
                                            <div class="d-flex justify-content-between gap-2 py-1 border-bottom text-tiny">
                                                <span>N°{{ $attempt->attempt_number }} — {{ $attempt->submitted_at->format('d/m/Y H:i') }}</span>
                                                <span class="mono-num">{{ $attempt->score }} / {{ $attempt->max_points }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">Informations</div>
                <div class="card-body">
                    <dl class="detail-list">
                        <div class="detail-row"><dt>Publication</dt><dd>{{ $quiz->published_at?->format('d/m/Y H:i') ?? 'Non publié' }}</dd></div>
                        <div class="detail-row"><dt>Date limite</dt><dd>{{ $quiz->due_at?->format('d/m/Y H:i') ?? 'Aucune' }}</dd></div>
                        <div class="detail-row"><dt>Durée indicative</dt><dd>{{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes.' min' : 'Libre' }}</dd></div>
                        <div class="detail-row"><dt>Barème</dt><dd>{{ $quiz->total_points }} points</dd></div>
                    </dl>
                    @if ($quiz->attempts()->exists())
                        <div class="alert alert-info small mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Des tentatives existent : les questions et le barème sont verrouillés afin de préserver les résultats.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let optionCounter = 0;

            document.querySelectorAll('.question-form').forEach((form) => {
                const container = form.querySelector('[data-options-container]');

                form.querySelector('[data-add-option]')?.addEventListener('click', () => {
                    optionCounter++;
                    const key = 'new_' + Date.now() + '_' + optionCounter;
                    const row = document.createElement('div');
                    row.className = 'input-group mb-2 option-row';
                    row.innerHTML = `
                        <span class="input-group-text" title="Bonne réponse">
                            <input class="form-check-input mt-0" type="checkbox" name="options[${key}][is_correct]" value="1" aria-label="Bonne réponse">
                        </span>
                        <input type="text" name="options[${key}][text]" class="form-control" placeholder="Nouvelle proposition" required>
                        <button type="button" class="btn btn-outline-danger" data-remove-option aria-label="Supprimer"><i class="bi bi-x-lg"></i></button>
                    `;
                    container.appendChild(row);
                    row.querySelector('input[type="text"]').focus();
                });

                container?.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-remove-option]');
                    if (!button) return;
                    if (container.querySelectorAll('.option-row').length <= 2) {
                        window.alert('Une question doit conserver au moins deux propositions.');
                        return;
                    }
                    button.closest('.option-row')?.remove();
                });
            });
        });
    </script>
@endpush
