@extends('layouts.app')

@section('title', 'Calendrier')
@section('topbar-title', 'Calendrier')

@section('content')
    @php
        $gridStart = $month->copy()->startOfMonth()->startOfWeek(\Carbon\Carbon::MONDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::SUNDAY);
        $cursor = $gridStart->copy();
        $weekdays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        $typeLabels = ['exercise' => 'Exercices', 'quiz' => 'Quiz', 'task' => 'Tâches'];
    @endphp

    <div class="page-header">
        <div>
            <h1 class="page-title mb-1">Calendrier</h1>
            <p class="page-subtitle mb-0">Échéances des exercices, quiz et tâches — {{ $month->translatedFormat('F Y') }}.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('calendar.index', array_filter(['month' => $previousMonth, 'subject' => $selectedSubject, 'type' => $selectedType])) }}" class="btn btn-outline-secondary btn-sm" aria-label="Mois précédent">
                <i class="bi bi-chevron-left"></i>
            </a>
            <a href="{{ route('calendar.index', array_filter(['subject' => $selectedSubject, 'type' => $selectedType])) }}" class="btn btn-outline-primary btn-sm">Aujourd’hui</a>
            <a href="{{ route('calendar.index', array_filter(['month' => $nextMonth, 'subject' => $selectedSubject, 'type' => $selectedType])) }}" class="btn btn-outline-secondary btn-sm" aria-label="Mois suivant">
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('calendar.index') }}" class="row g-2 align-items-end">
                <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="subject">Matière</label>
                    <select id="subject" name="subject" class="form-select" data-auto-submit>
                        <option value="">Toutes les matières</option>
                        @foreach ($subjects as $subjectItem)
                            <option value="{{ $subjectItem->id }}" @selected($selectedSubject == $subjectItem->id)>{{ $subjectItem->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="type">Type d’activité</label>
                    <select id="type" name="type" class="form-select" data-auto-submit>
                        <option value="">Tous les types</option>
                        @foreach ($typeLabels as $value => $label)
                            <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <button class="btn btn-outline-primary w-100" type="submit">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="calendar-shell mb-3">
        <div class="calendar-head">
            <div class="fw-semibold text-capitalize">{{ $month->translatedFormat('F Y') }}</div>
            <div class="meta-line">
                <span><span class="badge text-bg-light">{{ $events->count() }} événement(s)</span></span>
                <span class="text-tiny"><i class="bi bi-circle-fill" style="color:#1e4d6b"></i> Exercice</span>
                <span class="text-tiny"><i class="bi bi-circle-fill" style="color:#7a3e65"></i> Quiz</span>
                <span class="text-tiny"><i class="bi bi-circle-fill" style="color:#7a5c1e"></i> Tâche</span>
                <span class="text-tiny"><i class="bi bi-circle-fill" style="color:#2f6b4f"></i> Réalisé</span>
            </div>
        </div>

        <div class="calendar-grid">
            @foreach ($weekdays as $weekday)
                <div class="calendar-weekday">{{ $weekday }}</div>
            @endforeach

            @while ($cursor->lte($gridEnd))
                @php
                    $dayEvents = $eventsByDay->get($cursor->format('Y-m-d'), collect());
                    $isOutside = $cursor->month !== $month->month;
                @endphp
                <div class="calendar-day {{ $isOutside ? 'outside' : '' }} {{ $cursor->isToday() ? 'today' : '' }}">
                    <div class="calendar-day-number">{{ $cursor->day }}</div>
                    @foreach ($dayEvents->take(3) as $event)
                        <a href="{{ $event['url'] }}" class="calendar-event {{ $event['completed'] ? 'done' : '' }}" style="border-left-color: {{ $event['color'] }}" title="{{ $event['title'] }} — {{ $event['label'] }}">
                            {{ $event['title'] }}
                        </a>
                    @endforeach
                    @if ($dayEvents->count() > 3)
                        <span class="text-muted text-tiny">+{{ $dayEvents->count() - 3 }} autre(s)</span>
                    @endif
                </div>
                @php $cursor->addDay(); @endphp
            @endwhile
        </div>
    </div>

    <div class="calendar-list">
        <div class="card">
            <div class="card-header">Échéances du mois</div>
            <div class="card-body">
                @if ($events->isEmpty())
                    <x-empty-state icon="bi-calendar-x" title="Aucune échéance" text="Aucun événement pour ce mois et ces filtres." />
                @else
                    @php $previousDate = null; @endphp
                    @foreach ($events as $event)
                        @if ($previousDate !== $event['date']->format('Y-m-d'))
                            <div class="nav-label mt-3 mb-2">{{ $event['date']->translatedFormat('l d F') }}</div>
                            @php $previousDate = $event['date']->format('Y-m-d'); @endphp
                        @endif
                        <a href="{{ $event['url'] }}" class="d-flex gap-2 py-2 text-reset border-bottom {{ $event['completed'] ? 'opacity-75' : '' }}">
                            <span style="width:4px;border-radius:2px;background:{{ $event['color'] }};flex:0 0 auto"></span>
                            <div class="flex-grow-1">
                                <div class="small fw-semibold {{ $event['completed'] ? 'text-decoration-line-through' : '' }}">{{ $event['title'] }}</div>
                                <div class="meta-line">
                                    <span>{{ $event['label'] }}</span>
                                    <span>{{ $event['subject'] ?? 'Sans matière' }}</span>
                                    <span><i class="bi bi-clock me-1"></i>{{ $event['date']->format('H:i') }}</span>
                                    @if ($event['completed'])<span class="text-success">Réalisé</span>@endif
                                </div>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
@endsection
