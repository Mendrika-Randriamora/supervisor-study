@extends('layouts.app')

@section('title', 'Passer le quiz')
@section('topbar-title', $quiz->title)

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title mb-1">{{ $quiz->title }}</h1>
            <p class="page-subtitle mb-0">{{ $quiz->theme?->subject?->name }} — {{ $quiz->theme?->name }} · {{ $quiz->questions->count() }} question(s) · {{ $quiz->total_points }} points</p>
        </div>
        <div class="page-actions">
            @if ($quiz->time_limit_minutes)
                <span class="badge text-bg-light quiz-timer fs-6" data-quiz-timer>--:--</span>
            @endif
            <a href="{{ route('student.quizzes.show', $quiz) }}" class="btn btn-outline-secondary btn-sm" data-confirm="Quitter le quiz sans envoyer vos réponses ?">Quitter</a>
        </div>
    </div>

    <div class="alert alert-light border d-flex gap-2 align-items-start mb-3">
        <i class="bi bi-info-circle text-primary mt-1"></i>
        <div class="small">
            Sélectionnez une réponse par question (plusieurs pour les questions à choix multiples). Les bonnes réponses ne sont affichées qu’après l’envoi.
            <span class="text-muted" data-answered-counter></span>
        </div>
    </div>

    <form method="POST" action="{{ route('student.quizzes.submit', $quiz) }}" id="quizForm">
        @csrf
        <input type="hidden" name="duration_seconds" value="" data-quiz-duration>

        <div class="row g-3">
            <div class="col-12 col-xl-9">
                @foreach ($quiz->questions as $index => $question)
                    <div class="quiz-question">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <div class="small text-muted">Question {{ $index + 1 }} sur {{ $quiz->questions->count() }}</div>
                            <div class="d-flex gap-1">
                                <span class="badge text-bg-light">{{ $question->type->label() }}</span>
                                <span class="badge text-bg-light">{{ $question->points }} pt(s)</span>
                            </div>
                        </div>
                        <div class="fw-semibold mt-1 mb-3">{{ $question->question_text }}</div>

                        <div class="d-grid gap-2">
                            @foreach ($question->options as $option)
                                <label class="quiz-option" for="option-{{ $option->id }}">
                                    @if ($question->type === \App\Enums\QuizQuestionType::Multiple)
                                        <input class="form-check-input mt-0" type="checkbox" name="answers[{{ $question->id }}][]" id="option-{{ $option->id }}" value="{{ $option->id }}" data-answer-input>
                                    @else
                                        <input class="form-check-input mt-0" type="radio" name="answers[{{ $question->id }}]" id="option-{{ $option->id }}" value="{{ $option->id }}" data-answer-input>
                                    @endif
                                    <span class="small">{{ $option->option_text }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="col-12 col-xl-3">
                <div class="card" style="position: sticky; top: 80px">
                    <div class="card-header">Récapitulatif</div>
                    <div class="card-body">
                        <p class="small text-muted">Vérifiez que vous avez répondu à toutes les questions avant l’envoi. Une tentative est définitive une fois envoyée.</p>
                        <div class="progress mb-3">
                            <div class="progress-bar" style="width: 0%" data-answer-progress></div>
                        </div>
                        <div class="d-flex justify-content-between small mb-3">
                            <span class="text-muted">Répondu</span>
                            <span class="fw-semibold"><span data-answer-count>0</span> / {{ $quiz->questions->count() }}</span>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" data-confirm="Envoyer définitivement vos réponses ?">
                            <i class="bi bi-send me-1"></i>Envoyer mes réponses
                        </button>
                        <a href="{{ route('student.quizzes.show', $quiz) }}" class="btn btn-outline-secondary w-100 mt-2">Annuler</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('quizForm');
            if (!form) return;

            const startedAt = {{ (int) ($startedAt ?? now()->timestamp) }};
            const limitSeconds = {{ (int) ($quiz->time_limit_minutes ?? 0) * 60 }};
            const durationInput = form.querySelector('[data-quiz-duration]');
            const timer = document.querySelector('[data-quiz-timer]');
            const totalQuestions = {{ $quiz->questions->count() }};
            const inputs = Array.from(form.querySelectorAll('[data-answer-input]'));

            const answeredQuestions = () => {
                const names = new Set(inputs.filter((input) => input.checked).map((input) => input.name));
                return names.size;
            };

            const updateCounter = () => {
                const count = answeredQuestions();
                const label = form.closest('.app-content')?.querySelector('[data-answer-counter]');
                form.closest('.app-content')?.querySelectorAll('[data-answer-count]').forEach((element) => { element.textContent = count; });
                form.closest('.app-content')?.querySelectorAll('[data-answer-progress]').forEach((element) => {
                    element.style.width = totalQuestions > 0 ? (count / totalQuestions) * 100 + '%' : '0%';
                });
                if (label) {
                    label.textContent = count === totalQuestions
                        ? ' Toutes les questions ont une réponse.'
                        : ' ' + (totalQuestions - count) + ' question(s) sans réponse.';
                }
            };

            inputs.forEach((input) => input.addEventListener('change', updateCounter));
            updateCounter();

            let submitted = false;

            const tick = () => {
                const elapsed = Math.max(0, Math.floor(Date.now() / 1000) - startedAt);
                if (durationInput) durationInput.value = elapsed;

                if (!timer) return;

                if (limitSeconds > 0) {
                    const remaining = Math.max(0, limitSeconds - elapsed);
                    const minutes = String(Math.floor(remaining / 60)).padStart(2, '0');
                    const seconds = String(remaining % 60).padStart(2, '0');
                    timer.textContent = 'Temps restant ' + minutes + ':' + seconds;

                    if (remaining <= 0 && !submitted) {
                        submitted = true;
                        window.alert('Le temps conseillé est écoulé. Vos réponses vont être envoyées.');
                        form.submit();
                    }
                } else {
                    const minutes = String(Math.floor(elapsed / 60)).padStart(2, '0');
                    const seconds = String(elapsed % 60).padStart(2, '0');
                    timer.textContent = 'Temps ' + minutes + ':' + seconds;
                }
            };

            tick();
            window.setInterval(tick, 1000);

            form.addEventListener('submit', () => {
                submitted = true;
            });
        });
    </script>
@endpush
