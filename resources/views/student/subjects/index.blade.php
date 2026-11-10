@extends('layouts.app')

@section('title', 'Mes matières')
@section('topbar-title', 'Mes matières')

@section('content')
    <x-page-header title="Mes matières" subtitle="Parcourez vos matières, thèmes, exercices et quiz." />

    @if ($subjects->isEmpty())
        <div class="card">
            <x-empty-state icon="bi-journal-bookmark" title="Aucune matière disponible" text="Les matières publiées par votre superviseur apparaîtront ici." />
        </div>
    @else
        <div class="row g-3">
            @foreach ($subjects as $subject)
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span style="width:10px;height:10px;border-radius:50%;background:{{ $subject->color }}"></span>
                                <h2 class="h6 mb-0 flex-grow-1">
                                    <a href="{{ route('student.subjects.show', $subject) }}" class="text-reset">{{ $subject->name }}</a>
                                </h2>
                                @if ($subject->code)<span class="badge text-bg-light">{{ $subject->code }}</span>@endif
                            </div>
                            <p class="small text-muted mb-3">{{ $subject->description ?: 'Aucune description.' }}</p>

                            <div class="meta-line mb-3">
                                <span><i class="bi bi-diagram-3 me-1"></i>{{ $subject->themes->count() }} thème(s)</span>
                                <span><i class="bi bi-journal-check me-1"></i>{{ $subject->exercises_count }} exercice(s)</span>
                                <span><i class="bi bi-patch-question me-1"></i>{{ $subject->quizzes_count }} quiz</span>
                            </div>

                            @if ($subject->themes->isNotEmpty())
                                <div class="d-flex flex-wrap gap-1 mb-3">
                                    @foreach ($subject->themes->take(4) as $theme)
                                        <span class="badge text-bg-light">{{ Str::limit($theme->name, 22) }}</span>
                                    @endforeach
                                    @if ($subject->themes->count() > 4)
                                        <span class="badge text-bg-light">+{{ $subject->themes->count() - 4 }}</span>
                                    @endif
                                </div>
                            @endif

                            <a href="{{ route('student.subjects.show', $subject) }}" class="btn btn-outline-primary btn-sm mt-auto">
                                Ouvrir la matière <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
