@extends('layouts.app')

@section('title', 'Mon tableau de bord')
@section('topbar-title', 'Mon tableau de bord')

@section('content')
    @php
        $firstName = Str::of(auth()->user()->name)->explode(' ')->first();
        $remainingTasks = $overview['tasks']['total'] - $overview['tasks']['completed'];
        $urgent = $overview['upcoming']->filter(fn ($item) => $item['due_at']->diffInHours(now()) <= 48)->count();
    @endphp

    <div class="page-header">
        <div>
            <h1 class="page-title">Bonjour {{ $firstName }}</h1>
            <p class="page-subtitle">
                {{ now()->translatedFormat('l d F Y') }} —
                @if ($urgent > 0)
                    <span class="text-danger fw-semibold">{{ $urgent }} échéance(s) dans les 48 prochaines heures.</span>
                @else
                    aucune urgence immédiate.
                @endif
            </p>
        </div>
        <div class="page-actions">
            <a href="{{ route('student.tasks.index', ['status' => 'open']) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-list-task me-1"></i>Mes tâches</a>
            <a href="{{ route('student.exercises.index') }}" class="btn btn-primary btn-sm"><i class="bi bi-journal-check me-1"></i>Exercices à faire</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <x-stat-card label="Moyenne générale" :value="$overview['average'] !== null ? number_format($overview['average'], 2, ',', ' ').' /20' : '—'" icon="bi-graph-up-arrow" tone="primary" :hint="$overview['graded_count'].' activité(s) prise(s) en compte'" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Tâches restantes" :value="$remainingTasks" icon="bi-list-check" :tone="$overview['tasks']['overdue'] > 0 ? 'danger' : 'warning'" :hint="$overview['tasks']['overdue'].' en retard'" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Exercices à rendre" :value="$overview['exercises']['missing']" icon="bi-journal-text" hint="sur {{ $overview['exercises']['total'] }} publiés" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Quiz disponibles" :value="$overview['quizzes']['missing']" icon="bi-patch-question" hint="sur {{ $overview['quizzes']['total'] }} publiés" />
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Prochaines échéances</span>
                    <a href="{{ route('calendar.index') }}" class="small">Calendrier</a>
                </div>
                <div class="card-body">
                    @forelse ($overview['upcoming'] as $item)
                        <a href="{{ $item['url'] }}" class="d-flex gap-2 align-items-start py-2 text-reset {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <span class="stat-icon {{ $item['due_at']->diffInHours(now()) <= 48 ? 'danger' : 'primary' }}"><i class="bi {{ $item['icon'] }}"></i></span>
                            <div class="flex-grow-1">
                                <div class="small fw-semibold">{{ $item['title'] }}</div>
                                <div class="meta-line">
                                    <span>{{ $item['type'] }}</span>
                                    <span><i class="bi bi-clock me-1"></i>{{ $item['due_at']->format('d/m/Y H:i') }}</span>
                                    <span class="text-danger">dans {{ $item['due_at']->diffForHumans(now(), true) }}</span>
                                </div>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>
                    @empty
                        <x-empty-state icon="bi-calendar-check" title="Aucune échéance à venir" text="Vos prochaines activités datées apparaîtront ici." />
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card h-100">
                <div class="card-header">Mes dernières notes</div>
                <div class="card-body">
                    @forelse ($overview['recent_grades'] as $grade)
                        <a href="{{ $grade['url'] }}" class="d-flex justify-content-between gap-2 align-items-center py-2 text-reset {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <div>
                                <div class="small fw-semibold">{{ Str::limit($grade['title'], 38) }}</div>
                                <div class="meta-line">
                                    <span>{{ $grade['subject'] ?? 'Matière' }}</span>
                                    <span>{{ $grade['date']->format('d/m/Y') }}</span>
                                </div>
                            </div>
                            <span class="badge {{ $grade['converted'] >= 10 ? 'text-bg-success' : 'text-bg-danger' }}">{{ number_format($grade['converted'], 2, ',', ' ') }}/20</span>
                        </a>
                    @empty
                        <x-empty-state icon="bi-award" title="Aucune note pour le moment" text="Vos résultats apparaîtront après la correction de vos activités." />
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-7">
            <div class="card h-100">
                <div class="card-header">Évolution de mes notes</div>
                <div class="card-body">
                    @if ($overview['timeline']->isEmpty())
                        <x-empty-state icon="bi-graph-up" title="Pas encore de progression" text="Le graphique se construira à mesure que vos activités sont notées." />
                    @else
                        <div class="chart-wrap chart-sm">
                            <canvas id="studentProgressChart" aria-label="Évolution des notes de l’étudiant" role="img"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Notifications récentes</span>
                    <a href="{{ route('notifications.index') }}" class="small">Tout voir</a>
                </div>
                <div class="card-body">
                    @forelse ($notifications as $notification)
                        <a href="{{ route('notifications.open', $notification->id) }}" class="d-flex gap-2 py-2 text-reset {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <i class="bi {{ $notification->data['icon'] ?? 'bi-bell' }} text-primary mt-1"></i>
                            <div>
                                <div class="small fw-semibold">{{ $notification->data['title'] ?? 'Notification' }}</div>
                                <div class="text-muted text-tiny">{{ Str::limit($notification->data['message'] ?? '', 80) }}</div>
                                <div class="text-muted text-tiny mt-1">{{ $notification->created_at->diffForHumans() }}</div>
                            </div>
                            @if ($notification->unread())
                                <span class="badge text-bg-danger align-self-start ms-auto">Nouveau</span>
                            @endif
                        </a>
                    @empty
                        <x-empty-state icon="bi-bell-slash" title="Aucune notification" text="Vous serez informé des nouveaux exercices, quiz et corrections." />
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Mes matières</span>
            <a href="{{ route('student.subjects.index') }}" class="small">Toutes les matières</a>
        </div>
        <div class="card-body">
            @if ($overview['subjects']->isEmpty())
                <x-empty-state icon="bi-journal-bookmark" title="Aucune matière active" text="Les matières créées par votre superviseur apparaîtront ici." />
            @else
                <div class="row g-3">
                    @foreach ($overview['subjects'] as $subject)
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="soft-panel h-100">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span style="width:9px;height:9px;border-radius:50%;background:{{ $subject['color'] }}"></span>
                                    <a href="{{ route('student.subjects.show', $subject['id']) }}" class="fw-semibold small flex-grow-1 text-reset">{{ $subject['name'] }}</a>
                                    <span class="badge text-bg-light">{{ $subject['average'] !== null ? number_format($subject['average'], 2, ',', ' ') : '—' }}/20</span>
                                </div>
                                <div class="progress progress-thin mb-2">
                                    <div class="progress-bar" style="width: {{ $subject['exercises_total'] + $subject['quizzes_total'] > 0 ? round((($subject['completed']) / max(1, $subject['completed'] + $subject['remaining'])) * 100) : 0 }}%"></div>
                                </div>
                                <div class="meta-line">
                                    <span>{{ $subject['completed'] }} réalisée(s)</span>
                                    <span>{{ $subject['remaining'] }} restante(s)</span>
                                </div>
                                <div class="meta-line mt-1">
                                    <span>{{ $subject['tasks_completed'] }}/{{ $subject['tasks_total'] }} tâches</span>
                                    @if ($subject['success_rate'] !== null)
                                        <span>Réussite : {{ number_format($subject['success_rate'], 0) }}%</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    @if ($overview['timeline']->isNotEmpty())
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const canvas = document.getElementById('studentProgressChart');
                if (!canvas || !window.Chart) return;
                const timeline = @json($overview['timeline']);
                new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: timeline.map((item) => item.date),
                        datasets: [
                            {
                                label: 'Note /20',
                                data: timeline.map((item) => item.converted),
                                borderColor: '#1e4d6b',
                                backgroundColor: 'rgba(30, 77, 107, 0.08)',
                                pointBackgroundColor: '#1e4d6b',
                                pointRadius: 3,
                                tension: 0.25,
                                fill: true
                            },
                            {
                                label: 'Moyenne cumulée',
                                data: timeline.map((item) => item.running_average),
                                borderColor: '#8a5a2b',
                                borderDash: [4, 4],
                                pointRadius: 0,
                                tension: 0.25
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, font: { size: 11 } } } },
                        scales: {
                            y: { min: 0, max: 20, ticks: { stepSize: 5, font: { size: 11 } }, grid: { color: '#eef1f2' } },
                            x: { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 0, autoSkip: true } }
                        }
                    }
                });
            });
        </script>
    @endif
@endpush
