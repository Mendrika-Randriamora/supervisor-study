@extends('layouts.app')

@section('title', 'Matières')
@section('topbar-title', 'Matières et thèmes')

@section('content')
    <x-page-header title="Matières" subtitle="Organisez les contenus pédagogiques par matière puis par thème. L’archivage préserve les historiques.">
        <x-slot:actions>
            <a href="{{ route('supervisor.subjects.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nouvelle matière</a>
        </x-slot:actions>
    </x-page-header>

    @if ($subjects->isEmpty())
        <div class="card">
            <x-empty-state icon="bi-journal-bookmark" title="Aucune matière" text="Commencez par créer vos matières (Mathématiques, Physique-Chimie, etc.).">
                <a href="{{ route('supervisor.subjects.create') }}" class="btn btn-primary btn-sm">Créer une matière</a>
            </x-empty-state>
        </div>
    @else
        <div class="row g-3">
            @foreach ($subjects as $subject)
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100 {{ $subject->isArchived() ? 'opacity-75' : '' }}">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span style="width:11px;height:11px;border-radius:50%;background:{{ $subject->color }}"></span>
                                    <div>
                                        <a href="{{ route('supervisor.subjects.show', $subject) }}" class="fw-semibold text-reset">{{ $subject->name }}</a>
                                        <div class="text-muted text-tiny">{{ $subject->code ?: 'Sans code' }}</div>
                                    </div>
                                </div>
                                @if ($subject->isArchived())
                                    <span class="badge text-bg-dark">Archivée</span>
                                @endif
                            </div>

                            @if ($subject->description)
                                <p class="small text-muted mt-2 mb-2">{{ Str::limit($subject->description, 100) }}</p>
                            @endif

                            <div class="row g-2 mt-2 text-center">
                                <div class="col-3"><div class="small fw-semibold">{{ $subject->themes_count }}</div><div class="text-muted text-tiny">Thèmes</div></div>
                                <div class="col-3"><div class="small fw-semibold">{{ $subject->exercises_count }}</div><div class="text-muted text-tiny">Exercices</div></div>
                                <div class="col-3"><div class="small fw-semibold">{{ $subject->quizzes_count }}</div><div class="text-muted text-tiny">Quiz</div></div>
                                <div class="col-3"><div class="small fw-semibold">{{ $subject->tasks_count }}</div><div class="text-muted text-tiny">Tâches</div></div>
                            </div>

                            <div class="divider"></div>
                            <div class="d-flex flex-wrap gap-1">
                                <a href="{{ route('supervisor.subjects.show', $subject) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>Ouvrir</a>
                                <a href="{{ route('supervisor.subjects.edit', $subject) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                @if ($subject->isArchived())
                                    <form method="POST" action="{{ route('supervisor.subjects.unarchive', $subject) }}">
                                        @csrf @method('patch')
                                        <button class="btn btn-sm btn-outline-success" type="submit">Réactiver</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('supervisor.subjects.archive', $subject) }}">
                                        @csrf @method('patch')
                                        <button class="btn btn-sm btn-outline-secondary" type="submit" data-confirm="Archiver cette matière ? Les historiques resteront consultables.">Archiver</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
