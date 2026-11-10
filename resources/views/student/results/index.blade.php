@extends('layouts.app')

@section('title', 'Mes résultats')
@section('topbar-title', 'Mes résultats')

@section('content')
    <x-page-header title="Mes résultats" subtitle="Notes des exercices corrigés et des quiz réalisés." />

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><x-stat-card label="Moyenne générale" :value="$overview['average'] !== null ? number_format($overview['average'], 2, ',', ' ').' /20' : '—'" icon="bi-graph-up-arrow" tone="primary" :hint="$overview['graded_count'].' activité(s) notée(s)'" /></div>
        <div class="col-6 col-md-3"><x-stat-card label="Exercices corrigés" :value="$overview['exercises']['graded']" icon="bi-journal-check" hint="sur {{ $overview['exercises']['total'] }} publiés" /></div>
        <div class="col-6 col-md-3"><x-stat-card label="Quiz réalisés" :value="$overview['quizzes']['attempted']" icon="bi-patch-question" hint="{{ $overview['quizzes']['attempts'] }} tentative(s)" /></div>
        <div class="col-6 col-md-3"><x-stat-card label="Tâches terminées" :value="number_format($overview['tasks']['rate'], 1).' %'" icon="bi-list-check" :tone="$overview['tasks']['overdue'] > 0 ? 'danger' : 'success'" hint="$overview['tasks']['completed'] / $overview['tasks']['total']" /></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-7">
            <div class="card h-100">
                <div class="card-header">Évolution de mes résultats</div>
                <div class="card-body">
                    @if ($overview['timeline']->isEmpty())
                        <x-empty-state icon="bi-graph-up" title="Aucun résultat" text="Vos notes apparaîtront après la correction de vos activités." />
                    @else
                        <div class="chart-wrap"><canvas id="resultsTimelineChart" role="img" aria-label="Évolution des résultats"></canvas></div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="card h-100">
                <div class="card-header">Moyennes par matière</div>
                <div class="card-body">
                    @if ($overview['subjects']->whereNotNull('average')->isEmpty())
                        <x-empty-state icon="bi-bar-chart" title="Aucune moyenne" text="Les moyennes par matière s’afficheront ici." />
                    @else
                        <div class="chart-wrap"><canvas id="resultsSubjectsChart" role="img" aria-label="Moyennes par matière"></canvas></div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Détail par matière</div>
        <div class="card-body p-0">
            @if ($overview['subjects']->isEmpty())
                <x-empty-state icon="bi-journal-bookmark" title="Aucune matière suivie" />
            @else
                <div class="table-responsive table-desktop">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>Matière</th><th>Moyenne</th><th>Activités notées</th><th>Exercices rendus</th><th>Quiz réalisés</th><th>Tâches</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($overview['subjects'] as $subject)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span style="width:8px;height:8px;border-radius:50%;background:{{ $subject['color'] }}"></span>
                                            <a href="{{ route('student.subjects.show', $subject['id']) }}" class="fw-semibold text-reset">{{ $subject['name'] }}</a>
                                        </div>
                                    </td>
                                    <td class="mono-num fw-semibold">{{ $subject['average'] !== null ? number_format($subject['average'], 2, ',', ' ').' /20' : '—' }}</td>
                                    <td class="mono-num">{{ $subject['graded_count'] }}</td>
                                    <td class="mono-num">{{ $subject['exercises_submitted'] }} / {{ $subject['exercises_total'] }}</td>
                                    <td class="mono-num">{{ $subject['quizzes_attempted'] }} / {{ $subject['quizzes_total'] }}</td>
                                    <td class="mono-num">{{ $subject['tasks_completed'] }} / {{ $subject['tasks_total'] }}</td>
                                    <td class="text-end"><a href="{{ route('student.subjects.show', $subject['id']) }}" class="btn btn-sm btn-outline-secondary">Ouvrir</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mobile-list p-2">
                    @foreach ($overview['subjects'] as $subject)
                        <div class="mobile-list-item">
                            <div class="d-flex justify-content-between gap-2">
                                <span class="fw-semibold small">{{ $subject['name'] }}</span>
                                <span class="badge {{ $subject['average'] !== null && $subject['average'] >= 10 ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $subject['average'] !== null ? number_format($subject['average'], 2, ',', ' ').'/20' : '—' }}</span>
                            </div>
                            <div class="meta-line mt-1">
                                <span>{{ $subject['graded_count'] }} note(s)</span>
                                <span>{{ $subject['exercises_submitted'] }}/{{ $subject['exercises_total'] }} exercices</span>
                                <span>{{ $subject['quizzes_attempted'] }}/{{ $subject['quizzes_total'] }} quiz</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Toutes mes notes</span>
            <span class="text-muted text-tiny">Seules les activités notées sont prises en compte dans les moyennes</span>
        </div>
        <div class="card-body p-0">
            @if ($overview['activities']->isEmpty())
                <x-empty-state icon="bi-award" title="Aucune note" text="Vos résultats apparaîtront après la correction de vos activités." />
            @else
                <div class="table-responsive table-desktop">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>Activité</th><th>Type</th><th>Matière</th><th>Note</th><th>Sur 20</th><th>Date</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($overview['activities']->sortByDesc(fn ($activity) => $activity['date']->timestamp) as $activity)
                                <tr>
                                    <td class="fw-semibold small">{{ $activity['title'] }}</td>
                                    <td><span class="badge text-bg-light">{{ $activity['kind'] === 'quiz' ? 'Quiz' : 'Exercice' }}</span></td>
                                    <td class="small">{{ $activity['subject'] ?? '—' }}</td>
                                    <td class="mono-num">{{ $activity['score'] }} / {{ $activity['max_points'] }}</td>
                                    <td><span class="badge {{ $activity['converted'] >= 10 ? 'text-bg-success' : 'text-bg-danger' }}">{{ number_format($activity['converted'], 2, ',', ' ') }}</span></td>
                                    <td class="text-muted text-tiny">{{ $activity['date']->format('d/m/Y') }}</td>
                                    <td class="text-end"><a href="{{ $activity['url'] }}" class="btn btn-sm btn-outline-secondary">Détail</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mobile-list p-2">
                    @foreach ($overview['activities']->sortByDesc(fn ($activity) => $activity['date']->timestamp) as $activity)
                        <div class="mobile-list-item">
                            <div class="d-flex justify-content-between gap-2">
                                <span class="fw-semibold small">{{ Str::limit($activity['title'], 45) }}</span>
                                <span class="badge {{ $activity['converted'] >= 10 ? 'text-bg-success' : 'text-bg-danger' }}">{{ number_format($activity['converted'], 2, ',', ' ') }}/20</span>
                            </div>
                            <div class="meta-line mt-1">
                                <span>{{ $activity['kind'] === 'quiz' ? 'Quiz' : 'Exercice' }}</span>
                                <span>{{ $activity['subject'] ?? '—' }}</span>
                                <span>{{ $activity['date']->format('d/m/Y') }}</span>
                            </div>
                            <a href="{{ $activity['url'] }}" class="btn btn-sm btn-outline-secondary w-100 mt-2">Voir le détail</a>
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
            const timeline = @json($overview['timeline']);
            const subjects = @json($overview['subjects']->whereNotNull('average')->values());
            const colors = ['#1e4d6b', '#8a5a2b', '#2f6b4f', '#7a3e65', '#7a5c1e', '#3c5a7a', '#5b6b3a'];

            const timelineCanvas = document.getElementById('resultsTimelineChart');
            if (timelineCanvas && window.Chart && timeline.length > 0) {
                new Chart(timelineCanvas, {
                    type: 'line',
                    data: {
                        labels: timeline.map((item) => item.date),
                        datasets: [
                            {
                                label: 'Note /20',
                                data: timeline.map((item) => item.converted),
                                borderColor: '#1e4d6b',
                                backgroundColor: 'rgba(30, 77, 107, 0.08)',
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
            }

            const subjectsCanvas = document.getElementById('resultsSubjectsChart');
            if (subjectsCanvas && window.Chart && subjects.length > 0) {
                new Chart(subjectsCanvas, {
                    type: 'bar',
                    data: {
                        labels: subjects.map((subject) => subject.name),
                        datasets: [{
                            label: 'Moyenne /20',
                            data: subjects.map((subject) => subject.average),
                            backgroundColor: subjects.map((_, index) => colors[index % colors.length]),
                            borderRadius: 2,
                            maxBarThickness: 30
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { min: 0, max: 20, ticks: { stepSize: 5, font: { size: 11 } }, grid: { color: '#eef1f2' } },
                            x: { grid: { display: false }, ticks: { font: { size: 10 } } }
                        }
                    }
                });
            }
        });
    </script>
@endpush
