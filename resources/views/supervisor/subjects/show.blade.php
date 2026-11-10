@extends('layouts.app')

@section('title', $subject->name)
@section('topbar-title', 'Matière et thèmes')

@section('content')
    <div class="page-header">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span style="width:12px;height:12px;border-radius:50%;background:{{ $subject->color }}"></span>
                <h1 class="page-title mb-0">{{ $subject->name }}</h1>
                @if ($subject->isArchived())<span class="badge text-bg-dark">Archivée</span>@endif
            </div>
            <p class="page-subtitle mb-0">{{ $subject->description ?: 'Aucune description.' }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('supervisor.subjects.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
            <a href="{{ route('supervisor.subjects.edit', $subject) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Modifier</a>
            <a href="{{ route('supervisor.exercises.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-journal-plus me-1"></i>Exercice</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-7">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Thèmes</span>
                    <span class="badge text-bg-light">{{ $subject->themes->count() }}</span>
                </div>
                <div class="card-body p-0">
                    @forelse ($subject->themes as $theme)
                        <div class="px-3 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <div class="d-flex align-items-start justify-content-between gap-2">
                                <div>
                                    <div class="small fw-semibold">
                                        {{ $theme->name }}
                                        @if ($theme->isArchived())<span class="badge text-bg-dark ms-1">Archivé</span>@endif
                                    </div>
                                    <div class="meta-line">
                                        <span>{{ $theme->exercises_count }} exercice(s)</span>
                                        <span>{{ $theme->quizzes_count }} quiz</span>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-1">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#theme-edit-{{ $theme->id }}" aria-expanded="false"><i class="bi bi-pencil"></i></button>
                                    @if ($theme->isArchived())
                                        <form method="POST" action="{{ route('supervisor.themes.unarchive', $theme) }}">
                                            @csrf @method('patch')
                                            <button class="btn btn-sm btn-outline-success" type="submit">Réactiver</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('supervisor.themes.archive', $theme) }}">
                                            @csrf @method('patch')
                                            <button class="btn btn-sm btn-outline-secondary" type="submit" data-confirm="Archiver ce thème ?">Archiver</button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <div class="collapse mt-2" id="theme-edit-{{ $theme->id }}">
                                <form method="POST" action="{{ route('supervisor.themes.update', $theme) }}" class="row g-2 align-items-end">
                                    @csrf @method('patch')
                                    <div class="col-12 col-md-6">
                                        <label class="form-label" for="theme-name-{{ $theme->id }}">Nom du thème</label>
                                        <input class="form-control" id="theme-name-{{ $theme->id }}" name="name" value="{{ $theme->name }}" required>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label" for="theme-position-{{ $theme->id }}">Ordre</label>
                                        <input class="form-control" type="number" min="0" id="theme-position-{{ $theme->id }}" name="position" value="{{ $theme->position }}">
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <button class="btn btn-primary w-100" type="submit">OK</button>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="theme-description-{{ $theme->id }}">Description</label>
                                        <textarea class="form-control" rows="2" id="theme-description-{{ $theme->id }}" name="description">{{ $theme->description }}</textarea>
                                    </div>
                                    <div class="col-12 text-end">
                                        <button class="btn btn-sm btn-outline-danger" type="submit" form="delete-theme-{{ $theme->id }}" data-confirm="Supprimer ce thème ?">Supprimer</button>
                                    </div>
                                </form>
                                <form method="POST" action="{{ route('supervisor.themes.destroy', $theme) }}" id="delete-theme-{{ $theme->id }}">
                                    @csrf @method('delete')
                                </form>
                            </div>
                        </div>
                    @empty
                        <x-empty-state icon="bi-diagram-3" title="Aucun thème" text="Ajoutez un premier thème pour organiser les activités." />
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header">Ajouter un thème</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('supervisor.themes.store', $subject) }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="new-theme-name">Nom</label>
                            <input class="form-control @error('name') is-invalid @enderror" id="new-theme-name" name="name" value="{{ old('name') }}" placeholder="Dérivation" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-4 col-md-2">
                            <label class="form-label" for="new-theme-position">Ordre</label>
                            <input class="form-control" type="number" min="0" id="new-theme-position" name="position" value="{{ old('position', 0) }}">
                        </div>
                        <div class="col-8 col-md-4">
                            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-plus-lg me-1"></i>Ajouter</button>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="new-theme-description">Description</label>
                            <textarea class="form-control" rows="2" id="new-theme-description" name="description" placeholder="Facultatif">{{ old('description') }}</textarea>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Derniers exercices</span>
                    <a href="{{ route('supervisor.exercises.index', ['subject' => $subject->id]) }}" class="small">Tout voir</a>
                </div>
                <div class="card-body p-0">
                    @forelse ($recentExercises as $exercise)
                        <a href="{{ route('supervisor.exercises.show', $exercise) }}" class="d-flex justify-content-between gap-2 px-3 py-2 text-reset {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <div>
                                <div class="small fw-semibold">{{ Str::limit($exercise->title, 45) }}</div>
                                <div class="text-muted text-tiny">{{ $exercise->theme?->name }}</div>
                            </div>
                            <x-status-badge :status="$exercise->status" />
                        </a>
                    @empty
                        <x-empty-state icon="bi-journal" title="Aucun exercice" />
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Derniers quiz</span>
                    <a href="{{ route('supervisor.quizzes.index', ['subject' => $subject->id]) }}" class="small">Tout voir</a>
                </div>
                <div class="card-body p-0">
                    @forelse ($recentQuizzes as $quiz)
                        <a href="{{ route('supervisor.quizzes.show', $quiz) }}" class="d-flex justify-content-between gap-2 px-3 py-2 text-reset {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <div>
                                <div class="small fw-semibold">{{ Str::limit($quiz->title, 45) }}</div>
                                <div class="text-muted text-tiny">{{ $quiz->theme?->name }}</div>
                            </div>
                            <x-status-badge :status="$quiz->status" />
                        </a>
                    @empty
                        <x-empty-state icon="bi-patch-question" title="Aucun quiz" />
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
