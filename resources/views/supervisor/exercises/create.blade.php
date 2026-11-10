@extends('layouts.app')

@section('title', 'Nouvel exercice')
@section('topbar-title', 'Nouvel exercice')

@section('content')
    <x-page-header title="Nouvel exercice" subtitle="Choisissez un exercice PDF (copie photographiée) ou textuel (réponse en ligne)." />

    <div class="row g-3">
        <div class="col-12 col-xl-9">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('supervisor.exercises.store') }}" enctype="multipart/form-data">
                        @csrf
                        @include('supervisor.exercises._form', ['exercise' => null])
                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Enregistrer l’exercice</button>
                            <a href="{{ route('supervisor.exercises.index') }}" class="btn btn-outline-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-3">
            <div class="card">
                <div class="card-header">Conseils</div>
                <div class="card-body small text-muted">
                    <p>Un exercice <strong>PDF</strong> exige un document et reçoit des photos de copies.</p>
                    <p>Un exercice <strong>textuel</strong> reçoit une réponse saisie en ligne, avec copier-coller dissuadé.</p>
                    <p class="mb-0">La publication notifie tous les étudiants actifs. Vous pourrez corriger et annoter les copies.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
