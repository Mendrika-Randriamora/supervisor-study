@php
    $typeMeta = [
        'exercise_submitted' => ['icon' => 'bi-journal-arrow-up', 'tone' => 'primary', 'label' => 'Exercice soumis'],
        'exercise_graded' => ['icon' => 'bi-check2-circle', 'tone' => 'success', 'label' => 'Exercice corrigé'],
        'exercise_published' => ['icon' => 'bi-journal-plus', 'tone' => 'primary', 'label' => 'Exercice publié'],
        'quiz_published' => ['icon' => 'bi-patch-question', 'tone' => 'primary', 'label' => 'Quiz publié'],
        'quiz_submitted' => ['icon' => 'bi-check2-square', 'tone' => 'primary', 'label' => 'Quiz réalisé'],
        'revision_requested' => ['icon' => 'bi-arrow-repeat', 'tone' => 'warning', 'label' => 'Nouvelle réponse demandée'],
        'task_assigned' => ['icon' => 'bi-list-task', 'tone' => 'secondary', 'label' => 'Tâche attribuée'],
        'task_completed' => ['icon' => 'bi-check2-circle', 'tone' => 'success', 'label' => 'Tâche terminée'],
        'task_reopened' => ['icon' => 'bi-arrow-counterclockwise', 'tone' => 'warning', 'label' => 'Tâche rouverte'],
    ];

    $eventUrl = function ($event) {
        if (! $event->subject) {
            return null;
        }

        return match ($event->subject_type) {
            \App\Models\Exercise::class => auth()->user()->isSupervisor()
                ? route('supervisor.exercises.show', $event->subject_id)
                : route('student.exercises.show', $event->subject_id),
            \App\Models\Quiz::class => auth()->user()->isSupervisor()
                ? route('supervisor.quizzes.show', $event->subject_id)
                : route('student.quizzes.show', $event->subject_id),
            \App\Models\Task::class => auth()->user()->isSupervisor()
                ? route('supervisor.tasks.show', $event->subject_id)
                : route('student.tasks.show', $event->subject_id),
            \App\Models\ExerciseSubmission::class => auth()->user()->isSupervisor()
                ? route('supervisor.submissions.show', $event->subject_id)
                : route('student.submissions.show', $event->subject_id),
            default => null,
        };
    };
@endphp

@if ($events->isEmpty())
    <x-empty-state icon="bi-clock-history" title="Aucun événement" text="L’historique se remplira au fil des activités pédagogiques." />
@else
    <div class="timeline">
        @foreach ($events as $event)
            @php
                $meta = $typeMeta[$event->type] ?? ['icon' => 'bi-activity', 'tone' => 'secondary', 'label' => Str::headline($event->type)];
                $url = $eventUrl($event);
            @endphp
            <div class="timeline-item">
                <span class="stat-icon {{ $meta['tone'] }}"><i class="bi {{ $meta['icon'] }}"></i></span>
                <div class="flex-grow-1">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div>
                            @if ($url)
                                <a href="{{ $url }}" class="fw-semibold small text-reset">{{ $event->title }}</a>
                            @else
                                <span class="fw-semibold small">{{ $event->title }}</span>
                            @endif
                            @if ($event->description)
                                <div class="text-muted text-tiny">{{ $event->description }}</div>
                            @endif
                        </div>
                        <div class="text-end">
                            <span class="badge text-bg-light">{{ $meta['label'] }}</span>
                            <div class="text-muted text-tiny mt-1">{{ $event->occurred_at->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                    <div class="meta-line mt-1">
                        @if (! empty($showStudent) && $event->student)
                            <span><i class="bi bi-person me-1"></i>{{ $event->student->name }}</span>
                        @endif
                        @if ($event->actor && $event->actor_id !== $event->student_id)
                            <span><i class="bi bi-person-badge me-1"></i>Par {{ $event->actor->name }}</span>
                        @endif
                        <span><i class="bi bi-clock me-1"></i>{{ $event->occurred_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-3">{{ $events->links() }}</div>
@endif
