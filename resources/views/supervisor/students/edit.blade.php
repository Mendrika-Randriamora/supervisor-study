@extends('layouts.app')

@section('title', 'Modifier '.$student->name)
@section('topbar-title', 'Modifier un étudiant')

@section('content')
    <x-page-header :title="'Modifier '.$student->name" subtitle="Mettez à jour les informations du compte étudiant." />

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('supervisor.students.update', $student) }}">
                        @csrf
                        @method('patch')
                        @include('supervisor.students._form')
                        <div class="form-check mb-3">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active" @checked(old('is_active', $student->is_active))>
                            <label class="form-check-label small" for="is_active">Compte actif (autoriser la connexion)</label>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Enregistrer</button>
                            <a href="{{ route('supervisor.students.show', $student) }}" class="btn btn-outline-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header">Compte</div>
                <div class="card-body">
                    <dl class="detail-list">
                        <div class="detail-row"><dt>Créé le</dt><dd>{{ $student->created_at?->format('d/m/Y') }}</dd></div>
                        <div class="detail-row"><dt>Dernière connexion</dt><dd>{{ $student->last_login_at?->format('d/m/Y H:i') ?? 'Jamais' }}</dd></div>
                    </dl>
                    <div class="divider"></div>
                    <p class="small text-muted mb-2">La suppression est logique : l’historique académique reste conservé.</p>
                    <form method="POST" action="{{ route('supervisor.students.reset-password', $student) }}">
                        @csrf
                        <button class="btn btn-outline-secondary btn-sm w-100" type="submit" data-confirm="Réinitialiser le mot de passe de cet étudiant ?">
                            <i class="bi bi-key me-1"></i>Réinitialiser le mot de passe
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
