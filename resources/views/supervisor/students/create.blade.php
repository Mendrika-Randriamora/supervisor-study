@extends('layouts.app')

@section('title', 'Nouvel étudiant')
@section('topbar-title', 'Nouvel étudiant')

@section('content')
    <x-page-header title="Nouvel étudiant" subtitle="Créez un compte personnel. L’étudiant se connectera avec ces identifiants." />

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('supervisor.students.store') }}" autocomplete="off">
                        @csrf
                        @include('supervisor.students._form')
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Créer le compte</button>
                            <a href="{{ route('supervisor.students.index') }}" class="btn btn-outline-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header">Bon à savoir</div>
                <div class="card-body">
                    <p class="small text-muted mb-2">Aucune inscription publique n’est possible sur la plateforme.</p>
                    <ul class="small text-muted ps-3 mb-0">
                        <li>Le compte est actif immédiatement.</li>
                        <li>Communiquez le mot de passe de façon sécurisée.</li>
                        <li>Vous pourrez le réinitialiser depuis le profil de l’étudiant.</li>
                        <li>La désactivation bloque la connexion sans supprimer l’historique.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
