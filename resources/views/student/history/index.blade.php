@extends('layouts.app')

@section('title', 'Mon historique')
@section('topbar-title', 'Mon historique')

@section('content')
    @php
        $typeLabels = [
            'exercise_submitted' => 'Exercice soumis',
            'exercise_graded' => 'Exercice corrigé',
            'exercise_published' => 'Exercice publié',
            'quiz_published' => 'Quiz publié',
            'quiz_submitted' => 'Quiz réalisé',
            'revision_requested' => 'Nouvelle réponse demandée',
            'task_assigned' => 'Tâche attribuée',
            'task_completed' => 'Tâche terminée',
            'task_reopened' => 'Tâche rouverte',
        ];
    @endphp

    <x-page-header title="Mon historique" subtitle="Retrouvez toutes vos activités pédagogiques." />

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('student.history.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="subject">Matière</label>
                    <select id="subject" name="subject" class="form-select" data-auto-submit>
                        <option value="">Toutes</option>
                        @foreach ($subjects as $subjectItem)
                            <option value="{{ $subjectItem->id }}" @selected($selectedSubject == $subjectItem->id)>{{ $subjectItem->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="type">Type</label>
                    <select id="type" name="type" class="form-select" data-auto-submit>
                        <option value="">Tous</option>
                        @foreach ($types as $typeOption)
                            <option value="{{ $typeOption }}" @selected($selectedType === $typeOption)>{{ $typeLabels[$typeOption] ?? Str::headline($typeOption) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <button class="btn btn-outline-primary w-100" type="submit">Filtrer</button>
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label" for="from">Du</label>
                    <input type="date" id="from" name="from" value="{{ $from?->format('Y-m-d') }}" class="form-control">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label" for="to">Au</label>
                    <input type="date" id="to" name="to" value="{{ $to?->format('Y-m-d') }}" class="form-control">
                </div>
                <div class="col-12 col-md-4">
                    <a href="{{ route('student.history.index') }}" class="btn btn-outline-secondary w-100">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>{{ $events->total() }} événement(s)</span>
            <span class="text-muted text-tiny">Page {{ $events->currentPage() }} / {{ $events->lastPage() }}</span>
        </div>
        <div class="card-body">
            @include('partials.history-events', ['events' => $events, 'showStudent' => false])
        </div>
    </div>
@endsection
