@extends('layouts.app')

@section('title', 'Nouveau quiz')
@section('topbar-title', 'Nouveau quiz')

@section('content')
    <x-page-header title="Nouveau quiz" subtitle="Le quiz est créé en brouillon : ajoutez ensuite les questions avant publication." />

    <div class="row g-3">
        <div class="col-12 col-xl-9">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('supervisor.quizzes.store') }}">
                        @csrf
                        @include('supervisor.quizzes._form', ['quiz' => null])
                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Créer le brouillon</button>
                            <a href="{{ route('supervisor.quizzes.index') }}" class="btn btn-outline-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-3">
            <div class="card">
                <div class="card-header">Barème automatique</div>
                <div class="card-body small text-muted">
                    <p>Chaque question définit ses points. Le total du quiz est recalculé automatiquement.</p>
                    <p class="mb-0">Pour les choix multiples, la formule proportionnelle évite d’obtenir le maximum en cochant toutes les propositions.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
