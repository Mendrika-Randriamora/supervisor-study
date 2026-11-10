@extends('layouts.app')

@section('title', 'Modifier '.$subject->name)
@section('topbar-title', 'Modifier une matière')

@section('content')
    <x-page-header :title="'Modifier '.$subject->name" subtitle="Les modifications s’appliquent immédiatement aux activités futures." />

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('supervisor.subjects.update', $subject) }}">
                        @csrf
                        @method('patch')
                        @include('supervisor.subjects._form')
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Enregistrer</button>
                            <a href="{{ route('supervisor.subjects.show', $subject) }}" class="btn btn-outline-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header">Archivage</div>
                <div class="card-body">
                    <p class="small text-muted">L’archivage masque la matière des formulaires de création sans supprimer les activités, notes ni soumissions existantes.</p>
                    @if ($subject->isArchived())
                        <form method="POST" action="{{ route('supervisor.subjects.unarchive', $subject) }}">
                            @csrf @method('patch')
                            <button class="btn btn-outline-success w-100" type="submit">Réactiver la matière</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('supervisor.subjects.archive', $subject) }}">
                            @csrf @method('patch')
                            <button class="btn btn-outline-secondary w-100" type="submit" data-confirm="Archiver cette matière ?">Archiver la matière</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
