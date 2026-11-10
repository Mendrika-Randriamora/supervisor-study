@extends('layouts.app')

@section('title', 'Mon profil')

@section('content')
    <x-page-header title="Mon profil" subtitle="Mettez à jour vos informations de connexion personnelles." />

    <div class="row g-3">
        <div class="col-12 col-lg-5">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <span class="avatar avatar-lg">{{ $user->initials() }}</span>
                        <div>
                            <div class="fw-semibold">{{ $user->name }}</div>
                            <div class="text-muted small">{{ $user->role->label() }}</div>
                            <div class="text-muted text-tiny">{{ $user->email }}</div>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <dl class="detail-list mb-0">
                        <div class="detail-row">
                            <dt>Compte créé le</dt>
                            <dd>{{ $user->created_at?->format('d/m/Y') }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Dernière connexion</dt>
                            <dd>{{ $user->last_login_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Statut</dt>
                            <dd>
                                @if ($user->is_active)
                                    <span class="badge text-bg-success">Actif</span>
                                @else
                                    <span class="badge text-bg-danger">Désactivé</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card mb-3">
                <div class="card-header">Informations personnelles</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('patch')

                        <x-field name="name" label="Nom complet" :value="$user->name" required />
                        <x-field name="email" label="Adresse e-mail" type="email" :value="$user->email" required />

                        <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Enregistrer</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Sécurité</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        @method('put')

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mot de passe actuel</label>
                            <input type="password" id="current_password" name="current_password" autocomplete="current-password"
                                   class="form-control @error('current_password', 'updatePassword') is-invalid @enderror">
                            @error('current_password', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Nouveau mot de passe</label>
                            <input type="password" id="password" name="password" autocomplete="new-password"
                                   class="form-control @error('password', 'updatePassword') is-invalid @enderror">
                            @error('password', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <x-field name="password_confirmation" label="Confirmer le nouveau mot de passe" type="password" required />

                        <button type="submit" class="btn btn-outline-primary"><i class="bi bi-shield-lock me-1"></i>Modifier le mot de passe</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
