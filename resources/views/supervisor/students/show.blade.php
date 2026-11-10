@extends('layouts.app')

@section('title', 'Suivi de '.$student->name)
@section('topbar-title', 'Suivi individuel')

@section('content')
    <div class="page-header">
        <div class="d-flex align-items-center gap-3">
            <span class="avatar avatar-lg">{{ $student->initials() }}</span>
            <div>
                <h1 class="page-title mb-0">{{ $student->name }}</h1>
                <p class="page-subtitle mb-0">
                    {{ $student->email }}
                    @if ($student->is_active)
                        <span class="badge text-bg-success ms-1">Actif</span>
                    @else
                        <span class="badge text-bg-danger ms-1">Désactivé</span>
                    @endif
                </p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('supervisor.students.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
            <a href="{{ route('supervisor.students.edit', $student) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Modifier</a>
            <a href="{{ route('supervisor.history.index', ['student' => $student->id]) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-clock-history me-1"></i>Historique</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <x-stat-card label="Moyenne générale" :value="$overview['average'] !== null ? number_format($overview['average'], 2, ',', ' ').' /20' : '—'" icon="bi-graph-up-arrow" tone="primary" :hint="$overview['graded_count'].' activité(s) notée(s)'" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Exercices" :value="$overview['exercises']['submitted'].' / '.$overview['exercises']['total']" icon="bi-journal-check" hint="{{ $overview['exercises']['missing'] }} non rendu(s)" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Quiz réalisés" :value="$overview['quizzes']['attempted'].' / '.$overview['quizzes']['total']" icon="bi-patch-question" hint="{{ $overview['quizzes']['attempts'] }} tentative(s) au total" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Tâches terminées" :value="$overview['tasks']['completed'].' / '.$overview['tasks']['total']" icon="bi-list-task" :tone="$overview['tasks']['overdue'] > 0 ? 'danger' : 'success'" :hint="number_format($overview['tasks']['rate'], 1).' % — '.$overview['tasks']['overdue'].' en retard'" />
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-8">
            <div class="card h-100">
                <div class="card-header">Évolution des notes</div>
                <div class="card-body">
                    @if ($overview['timeline']->isEmpty())
                        <x-empty-state icon="bi-graph-up" title="Aucune note disponible" text="Les activités corrigées alimenteront ce graphique." />
                    @else
                        <div class="chart-wrap"><canvas id="studentTimelineChart" role="img" aria-label="Évolution des notes"></canvas></div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header">Réalisation des tâches</div>
                <div class="card-body">
                    <div class="d-flex align-items-baseline gap-2 mb-2">
                        <span class="stat-value">{{ number_format($overview['tasks']['rate'], 1) }}%</span>
                        <span class="text-muted small">terminées</span>
                    </div>
                    <div class="progress mb-3"><div class="progress-bar bg-success" style="width: {{ $overview['tasks']['rate'] }}%"></div></div>
                    <dl class="detail-list">
                        <div class="detail-row"><dt>Terminées</dt><dd>{{ $overview['tasks']['completed'] }}</dd></div>
                        <div class="detail-row"><dt>En retard</dt><dd class="{{ $overview['tasks']['overdue'] > 0 ? 'text-danger' : '' }}">{{ $overview['tasks']['overdue'] }}</dd></div>
                        <div class="detail-row"><dt>Total attribué</dt><dd>{{ $overview['tasks']['total'] }}</dd></div>
                    </dl>
                    @if ($overview['task_chart']['labels'])
                        <div class="chart-wrap chart-sm mt-3"><canvas id="studentTaskChart" role="img" aria-label="Tâches terminées par mois"></canvas></div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header">Comparaison des matières</div>
                <div class="card-body">
                    @if ($overview['subjects']->whereNotNull('average')->isEmpty())
                        <x-empty-state icon="bi-bar-chart" title="Pas encore de moyennes" text="Les matières disposant de notes s’afficheront ici." />
                    @else
                        <div class="chart-wrap chart-sm"><canvas id="studentSubjectsChart" role="img" aria-label="Moyennes par matière"></canvas></div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header">Informations du compte</div>
                <div class="card-body">
                    <dl class="detail-list">
                        <div class="detail-row"><dt>Téléphone</dt><dd>{{ $student->phone ?? '—' }}</dd></div>
                        <div class="detail-row"><dt>Créé le</dt><dd>{{ $student->created_at?->format('d/m/Y') }}</dd></div>
                        <div class="detail-row"><dt>Dernière connexion</dt><dd>{{ $student->last_login_at?->format('d/m/Y H:i') ?? 'Jamais' }}</dd></div>
                        <div class="detail-row"><dt>Soumissions à corriger</dt><dd>{{ $pendingCount }}</dd></div>
                    </dl>

                    @if ($student->notes)
                        <div class="divider"></div>
                        <div class="small fw-semibold mb-1">Notes internes</div>
                        <p class="small text-muted mb-0">{{ $student->notes }}</p>
                    @endif

                    <div class="divider"></div>
                    <div class="d-grid gap-2">
                        <form method="POST" action="{{ route('supervisor.students.reset-password', $student) }}">
                            @csrf
                            <button class="btn btn-outline-secondary btn-sm w-100" type="submit" data-confirm="Réinitialiser le mot de passe de cet étudiant ?">
                                <i class="bi bi-key me-1"></i>Réinitialiser le mot de passe
                            </button>
                        </form>
                        @if ($student->is_active)
                            <form method="POST" action="{{ route('supervisor.students.deactivate', $student) }}">
                                @csrf @method('patch')
                                <button class="btn btn-outline-warning btn-sm w-100" type="submit" data-confirm="Désactiver ce compte ? L’étudiant ne pourra plus se connecter.">
                                    <i class="bi bi-pause-circle me-1"></i>Désactiver le compte
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('supervisor.students.activate', $student) }}">
                                @csrf @method('patch')
                                <button class="btn btn-outline-success btn-sm w-100" type="submit"><i class="bi bi-play-circle me-1"></i>Réactiver le compte</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('supervisor.students.destroy', $student) }}">
                            @csrf @method('delete')
                            <button class="btn btn-outline-danger btn-sm w-100" type="submit" data-confirm="Supprimer (archiver) ce compte étudiant ? L’historique académique sera conservé.">
                                <i class="bi bi-trash me-1"></i>Supprimer le compte
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Performances par matière</span>
            <span class="text-muted text-tiny">Moyennes sur les activités notées</span>
        </div>
        <div class="card-body p-0">
            @if ($overview['subjects']->isEmpty())
                <x-empty-state icon="bi-journal-bookmark" title="Aucune matière suivie" text="Créez des matières et publiez des activités pour alimenter le suivi." />
            @else
                <div class="table-responsive table-desktop">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>Matière</th><th>Moyenne</th><th>Activités</th><th>Restantes</th><th>Tâches</th><th>Réussite</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($overview['subjects'] as $subject)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span style="width:8px;height:8px;border-radius:50%;background:{{ $subject['color'] }}"></span>
                                            <span class="fw-semibold">{{ $subject['name'] }}</span>
                                        </div>
                                    </td>
                                    <td class="mono-num">{{ $subject['average'] !== null ? number_format($subject['average'], 2, ',', ' ') : '—' }}</td>
                                    <td class="mono-num">{{ $subject['completed'] }}</td>
                                    <td class="mono-num">{{ $subject['remaining'] }}</td>
                                    <td class="mono-num">{{ $subject['tasks_completed'] }}/{{ $subject['tasks_total'] }}</td>
                                    <td>
                                        @if ($subject['success_rate'] !== null)
                                            <span class="badge {{ $subject['success_rate'] >= 50 ? 'text-bg-success' : 'text-bg-warning' }}">{{ number_format($subject['success_rate'], 0) }}%</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mobile-list p-2">
                    @foreach ($overview['subjects'] as $subject)
                        <div class="mobile-list-item">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold small"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:{{ $subject['color'] }};margin-right:5px"></span>{{ $subject['name'] }}</span>
                                <span class="badge text-bg-light">{{ $subject['average'] !== null ? number_format($subject['average'], 2, ',', ' ') : '—' }}/20</span>
                            </div>
                            <div class="meta-line mt-1">
                                <span>{{ $subject['completed'] }} réalisée(s)</span>
                                <span>{{ $subject['remaining'] }} restante(s)</span>
                                <span>{{ $subject['tasks_completed'] }}/{{ $subject['tasks_total'] }} tâches</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header">Dernières soumissions</div>
                <div class="card-body p-0">
                    @forelse ($submissions as $submission)
                        <a href="{{ route('supervisor.submissions.show', $submission) }}" class="d-flex justify-content-between gap-2 align-items-center px-3 py-2 text-reset {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <div>
                                <div class="small fw-semibold">{{ Str::limit($submission->exercise?->title, 45) }}</div>
                                <div class="meta-line">
                                    <span>{{ $submission->exercise?->theme?->subject?->name }}</span>
                                    <span>v{{ $submission->version }} — {{ $submission->submitted_at->format('d/m/Y H:i') }}</span>
                                </div>
                            </div>
                            <x-status-badge :status="$submission->status" />
                        </a>
                    @empty
                        <x-empty-state icon="bi-inbox" title="Aucune soumission" text="Les copies envoyées par l’étudiant apparaîtront ici." />
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header">Dernières tentatives de quiz</div>
                <div class="card-body p-0">
                    @forelse ($attempts as $attempt)
                        <a href="{{ route('supervisor.quizzes.show', $attempt->quiz) }}" class="d-flex justify-content-between gap-2 align-items-center px-3 py-2 text-reset {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <div>
                                <div class="small fw-semibold">{{ Str::limit($attempt->quiz?->title, 45) }}</div>
                                <div class="meta-line">
                                    <span>{{ $attempt->quiz?->theme?->subject?->name }}</span>
                                    <span>Tentative {{ $attempt->attempt_number }} — {{ $attempt->submitted_at->format('d/m/Y H:i') }}</span>
                                </div>
                            </div>
                            <span class="badge {{ $attempt->convertedTo20() >= 10 ? 'text-bg-success' : 'text-bg-danger' }}">{{ number_format($attempt->convertedTo20(), 2, ',', ' ') }}/20</span>
                        </a>
                    @empty
                        <x-empty-state icon="bi-patch-question" title="Aucun quiz réalisé" text="Les tentatives de quiz apparaîtront ici." />
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Historique récent</span>
            <a href="{{ route('supervisor.history.index', ['student' => $student->id]) }}" class="small">Historique complet</a>
        </div>
        <div class="card-body">
            @forelse ($events as $event)
                <div class="d-flex gap-2 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                    <span class="stat-icon"><i class="bi bi-activity"></i></span>
                    <div class="flex-grow-1">
                        <div class="small fw-semibold">{{ $event->title }}</div>
                        @if ($event->description)
                            <div class="text-muted text-tiny">{{ Str::limit($event->description, 120) }}</div>
                        @endif
                        <div class="meta-line mt-1"><span>{{ $event->occurred_at->format('d/m/Y H:i') }}</span></div>
                    </div>
                </div>
            @empty
                <x-empty-state icon="bi-clock-history" title="Aucun événement" text="L’activité académique de cet étudiant apparaîtra ici." />
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.Chart) return;

            const timeline = @json($overview['timeline']);
            const timelineCanvas = document.getElementById('studentTimelineChart');
            if (timelineCanvas && timeline.length) {
                new Chart(timelineCanvas, {
                    type: 'line',
                    data: {
                        labels: timeline.map((item) => item.date),
                        datasets: [
                            { label: 'Note /20', data: timeline.map((item) => item.converted), borderColor: '#1e4d6b', backgroundColor: 'rgba(30,77,107,.08)', fill: true, pointRadius: 3, tension: .25 },
                            { label: 'Moyenne cumulée', data: timeline.map((item) => item.running_average), borderColor: '#8a5a2b', borderDash: [4, 4], pointRadius: 0, tension: .25 }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, font: { size: 11 } } } },
                        scales: { y: { min: 0, max: 20, ticks: { stepSize: 5 } }, x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true } } }
                    }
                });
            }

            const subjects = @json($overview['subjects']->whereNotNull('average')->values());
            const subjectsCanvas = document.getElementById('studentSubjectsChart');
            if (subjectsCanvas && subjects.length) {
                new Chart(subjectsCanvas, {
                    type: 'bar',
                    data: {
                        labels: subjects.map((item) => item.name),
                        datasets: [{ label: 'Moyenne /20', data: subjects.map((item) => item.average), backgroundColor: subjects.map((item) => item.color || '#1e4d6b'), borderRadius: 2, maxBarThickness: 34 }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { min: 0, max: 20, ticks: { stepSize: 5 } }, x: { grid: { display: false }, ticks: { font: { size: 10 } } } }
                    }
                });
            }

            const taskChart = @json($overview['task_chart']);
            const taskCanvas = document.getElementById('studentTaskChart');
            if (taskCanvas && taskChart) {
                new Chart(taskCanvas, {
                    type: 'bar',
                    data: {
                        labels: taskChart.labels,
                        datasets: [
                            { label: 'Attribuées', data: taskChart.assigned, backgroundColor: '#c9d3d9', borderRadius: 2 },
                            { label: 'Terminées', data: taskChart.completed, backgroundColor: '#2f6b4f', borderRadius: 2 }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, boxHeight: 8, font: { size: 10 } } } },
                        scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false }, ticks: { font: { size: 9 } } } }
                    }
                });
            }
        });
    </script>
@endpush
