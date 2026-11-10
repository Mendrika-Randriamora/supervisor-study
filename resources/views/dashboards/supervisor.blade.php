@extends('layouts.app')

@section('title', 'Tableau de bord superviseur')
@section('topbar-title', 'Tableau de bord superviseur')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Bonjour, {{ auth()->user()->name }}</h1>
            <p class="page-subtitle">Vue d’ensemble du travail pédagogique — {{ now()->translatedFormat('l d F Y') }}.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('supervisor.students.create') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-person-plus me-1"></i>Nouvel étudiant</a>
            <a href="{{ route('supervisor.exercises.create') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-journal-plus me-1"></i>Nouvel exercice</a>
            <a href="{{ route('supervisor.notifications.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-megaphone me-1"></i>Envoyer un message</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-4 col-xl-2">
            <x-stat-card label="Étudiants" :value="$overview['students_count']" icon="bi-people" hint="comptes actifs" />
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <x-stat-card label="Exercices" :value="$overview['published_exercises']" icon="bi-journal-check" tone="primary" hint="publiés" />
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <x-stat-card label="Quiz" :value="$overview['published_quizzes']" icon="bi-patch-question" hint="publiés" />
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <x-stat-card label="À corriger" :value="$overview['pending_submissions']" icon="bi-inbox" :tone="$overview['pending_submissions'] > 0 ? 'warning' : 'success'" hint="soumissions en attente" />
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <x-stat-card label="Tâches ouvertes" :value="$overview['in_progress_tasks']" icon="bi-list-task" hint="non terminées" />
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <x-stat-card label="En retard" :value="$overview['overdue_tasks']" icon="bi-alarm" :tone="$overview['overdue_tasks'] > 0 ? 'danger' : 'success'" hint="tâches dépassées" />
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>Activité des six derniers mois</span>
                    <span class="text-muted text-tiny">Soumissions et corrections</span>
                </div>
                <div class="card-body">
                    <div class="chart-wrap">
                        <canvas id="globalActivityChart" aria-label="Graphique des soumissions et corrections" role="img"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header">Réalisation des tâches</div>
                <div class="card-body">
                    <div class="d-flex align-items-baseline gap-2 mb-1">
                        <span class="stat-value">{{ $overview['task_completion_rate'] }}%</span>
                        <span class="text-muted small">taux global</span>
                    </div>
                    <div class="progress mb-3" role="progressbar" aria-label="Taux de réalisation des tâches" aria-valuenow="{{ $overview['task_completion_rate'] }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar" style="width: {{ $overview['task_completion_rate'] }}%"></div>
                    </div>
                    <p class="text-muted small mb-3">Le taux compare les tâches terminées à l’ensemble des tâches attribuées. Les tâches ne sont jamais intégrées aux moyennes académiques.</p>
                    <div class="d-flex flex-column gap-2">
                        <a href="{{ route('supervisor.tasks.index', ['status' => 'open']) }}" class="btn btn-outline-secondary btn-sm d-flex justify-content-between">
                            <span><i class="bi bi-list-task me-1"></i>Tâches ouvertes</span>
                            <strong>{{ $overview['in_progress_tasks'] }}</strong>
                        </a>
                        <a href="{{ route('supervisor.tasks.index', ['status' => 'overdue']) }}" class="btn btn-outline-secondary btn-sm d-flex justify-content-between">
                            <span><i class="bi bi-alarm me-1"></i>Tâches en retard</span>
                            <strong>{{ $overview['overdue_tasks'] }}</strong>
                        </a>
                        <a href="{{ route('supervisor.history.index') }}" class="btn btn-outline-secondary btn-sm d-flex justify-content-between">
                            <span><i class="bi bi-clock-history me-1"></i>Historique complet</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-7">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>Soumissions à corriger</span>
                    <a href="{{ route('supervisor.submissions.index', ['status' => 'submitted']) }}" class="small">Tout voir</a>
                </div>
                <div class="card-body p-0">
                    @if ($overview['pending_submissions_list']->isEmpty())
                        <x-empty-state icon="bi-check2-circle" title="Aucune soumission en attente" text="Toutes les copies reçues ont été corrigées." />
                    @else
                        <div class="table-responsive table-desktop">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr><th>Étudiant</th><th>Exercice</th><th>Envoyé</th><th></th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($overview['pending_submissions_list'] as $submission)
                                        <tr class="hover-row">
                                            <td>
                                                <div class="fw-semibold">{{ $submission->student?->name }}</div>
                                                <div class="text-muted text-tiny">{{ $submission->exercise?->theme?->subject?->name }}</div>
                                            </td>
                                            <td>{{ Str::limit($submission->exercise?->title, 40) }}</td>
                                            <td class="text-muted text-tiny">{{ $submission->submitted_at->format('d/m H:i') }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('supervisor.submissions.show', $submission) }}" class="btn btn-sm btn-outline-primary">Corriger</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mobile-list p-2">
                            @foreach ($overview['pending_submissions_list'] as $submission)
                                <div class="mobile-list-item">
                                    <div class="d-flex justify-content-between gap-2">
                                        <div class="fw-semibold small">{{ $submission->student?->name }}</div>
                                        <span class="badge text-bg-warning">À corriger</span>
                                    </div>
                                    <div class="small mt-1">{{ Str::limit($submission->exercise?->title, 60) }}</div>
                                    <div class="meta-line mt-1">
                                        <span><i class="bi bi-clock me-1"></i>{{ $submission->submitted_at->format('d/m H:i') }}</span>
                                    </div>
                                    <a href="{{ route('supervisor.submissions.show', $submission) }}" class="btn btn-sm btn-outline-primary mt-2 w-100">Corriger</a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>Activités récentes</span>
                    <a href="{{ route('supervisor.history.index') }}" class="small">Historique</a>
                </div>
                <div class="card-body">
                    @forelse ($overview['recent_events'] as $event)
                        <div class="d-flex gap-2 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <span class="stat-icon primary"><i class="bi bi-activity"></i></span>
                            <div class="flex-grow-1">
                                <div class="small fw-semibold">{{ $event->title }}</div>
                                <div class="meta-line">
                                    <span>{{ $event->student?->name }}</span>
                                    <span>{{ $event->occurred_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <x-empty-state icon="bi-clock-history" title="Aucune activité" text="Les actions pédagogiques apparaîtront ici." />
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span>Suivi individuel</span>
            <a href="{{ route('supervisor.students.index') }}" class="small">Tous les étudiants</a>
        </div>
        <div class="card-body p-0">
            @if ($overview['student_summaries']->isEmpty())
                <x-empty-state icon="bi-people" title="Aucun étudiant" text="Créez un premier compte étudiant pour commencer le suivi.">
                    <a href="{{ route('supervisor.students.create') }}" class="btn btn-primary btn-sm">Créer un étudiant</a>
                </x-empty-state>
            @else
                <div class="table-responsive table-desktop">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>Étudiant</th><th>Moyenne /20</th><th>Activités notées</th><th>Tâches</th><th>Exercices non rendus</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($overview['student_summaries'] as $summary)
                                <tr class="hover-row">
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar">{{ $summary['student']->initials() }}</span>
                                            <div>
                                                <div class="fw-semibold">{{ $summary['student']->name }}</div>
                                                <div class="text-muted text-tiny">{{ $summary['student']->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="mono-num fw-semibold">{{ $summary['average'] !== null ? number_format($summary['average'], 2, ',', ' ') : '—' }}</td>
                                    <td class="mono-num">{{ $summary['graded_count'] }}</td>
                                    <td class="mono-num">{{ number_format($summary['task_rate'], 0) }}%</td>
                                    <td><span class="badge {{ $summary['missing_exercises'] > 0 ? 'text-bg-warning' : 'text-bg-success' }}">{{ $summary['missing_exercises'] }}</span></td>
                                    <td class="text-end"><a href="{{ route('supervisor.students.show', $summary['student']) }}" class="btn btn-sm btn-outline-secondary">Voir le suivi</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mobile-list p-2">
                    @foreach ($overview['student_summaries'] as $summary)
                        <div class="mobile-list-item">
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar">{{ $summary['student']->initials() }}</span>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold small">{{ $summary['student']->name }}</div>
                                    <div class="meta-line">
                                        <span>Moyenne : {{ $summary['average'] !== null ? number_format($summary['average'], 2, ',', ' ') : '—' }} /20</span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-2 small text-muted">
                                <span>{{ $summary['graded_count'] }} activité(s) notée(s)</span>
                                <span>{{ number_format($summary['task_rate'], 0) }}% tâches</span>
                            </div>
                            <a href="{{ route('supervisor.students.show', $summary['student']) }}" class="btn btn-sm btn-outline-secondary w-100 mt-2">Voir le suivi</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const canvas = document.getElementById('globalActivityChart');
            if (!canvas || !window.Chart) return;
            const data = @json($overview['global_task_chart']);
            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        { label: 'Soumissions', data: data.submitted, backgroundColor: '#1e4d6b', borderRadius: 2, maxBarThickness: 26 },
                        { label: 'Corrections', data: data.graded, backgroundColor: '#9fb6c3', borderRadius: 2, maxBarThickness: 26 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, font: { size: 11 } } } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } }, grid: { color: '#eef1f2' } },
                        x: { grid: { display: false }, ticks: { font: { size: 11 } } }
                    }
                }
            });
        });
    </script>
@endpush
