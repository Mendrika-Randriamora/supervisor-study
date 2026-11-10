@extends('layouts.app')

@section('title', 'Quiz')
@section('topbar-title', 'Gestion des quiz')

@section('content')
    <x-page-header title="Quiz" subtitle="Créez des quiz à choix unique ou multiple, avec correction automatique et tentatives conservées.">
        <x-slot:actions>
            <a href="{{ route('supervisor.quizzes.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nouveau quiz</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-8 col-md-5">
                    <label class="form-label" for="subject">Matière</label>
                    <select id="subject" name="subject" class="form-select" data-auto-submit>
                        <option value="">Toutes</option>
                        @foreach ($subjects as $subjectItem)
                            <option value="{{ $subjectItem->id }}" @selected($selectedSubject == $subjectItem->id)>{{ $subjectItem->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4 col-md-4">
                    <label class="form-label" for="status">Statut</label>
                    <select id="status" name="status" class="form-select" data-auto-submit>
                        <option value="">Tous</option>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption->value }}" @selected($status === $statusOption->value)>{{ $statusOption->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <button class="btn btn-outline-primary w-100" type="submit">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">{{ $quizzes->total() }} quiz</div>
        <div class="card-body p-0">
            @if ($quizzes->isEmpty())
                <x-empty-state icon="bi-patch-question" title="Aucun quiz" text="Créez un quiz, ajoutez des questions puis publiez-le.">
                    <a href="{{ route('supervisor.quizzes.create') }}" class="btn btn-primary btn-sm">Créer un quiz</a>
                </x-empty-state>
            @else
                <div class="table-responsive table-desktop">
                    <table class="table table-hover">
                        <thead><tr><th>Quiz</th><th>Matière / thème</th><th>Questions</th><th>Points</th><th>Tentatives</th><th>Statut</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($quizzes as $quiz)
                                <tr class="hover-row">
                                    <td><a href="{{ route('supervisor.quizzes.show', $quiz) }}" class="fw-semibold text-reset">{{ Str::limit($quiz->title, 45) }}</a></td>
                                    <td><div class="small">{{ $quiz->theme?->subject?->name }}</div><div class="text-muted text-tiny">{{ $quiz->theme?->name }}</div></td>
                                    <td class="mono-num">{{ $quiz->questions_count }}</td>
                                    <td class="mono-num">{{ $quiz->total_points }}</td>
                                    <td class="mono-num">{{ $quiz->attempts_count }}</td>
                                    <td><x-status-badge :status="$quiz->status" /></td>
                                    <td class="text-end"><a href="{{ route('supervisor.quizzes.show', $quiz) }}" class="btn btn-sm btn-outline-primary">Ouvrir</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mobile-list p-2">
                    @foreach ($quizzes as $quiz)
                        <div class="mobile-list-item">
                            <div class="d-flex justify-content-between gap-2">
                                <a href="{{ route('supervisor.quizzes.show', $quiz) }}" class="fw-semibold small text-reset">{{ Str::limit($quiz->title, 55) }}</a>
                                <x-status-badge :status="$quiz->status" />
                            </div>
                            <div class="meta-line mt-1">
                                <span>{{ $quiz->theme?->subject?->name }}</span>
                                <span>{{ $quiz->questions_count }} question(s)</span>
                                <span>{{ $quiz->attempts_count }} tentative(s)</span>
                            </div>
                            <a href="{{ route('supervisor.quizzes.show', $quiz) }}" class="btn btn-sm btn-outline-primary w-100 mt-2">Ouvrir</a>
                        </div>
                    @endforeach
                </div>
                <div class="p-3">{{ $quizzes->links() }}</div>
            @endif
        </div>
    </div>
@endsection
