@extends('layouts.guest')

@section('title', 'Connexion')

@section('content')
    <div class="mb-4">
        <h1 class="h4 mb-1">Connexion</h1>
        <p class="text-muted small mb-0">Accédez à votre espace de suivi pédagogique.</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success small">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Adresse e-mail</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="form-control @error('email') is-invalid @enderror" placeholder="vous@exemple.com">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Mot de passe</label>
            <div class="input-group">
                <input type="password" id="password" name="password" required autocomplete="current-password"
                       class="form-control @error('password') is-invalid @enderror">
                <button class="btn btn-outline-secondary" type="button" data-password-toggle="password" aria-label="Afficher le mot de passe">
                    <i class="bi bi-eye"></i>
                </button>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-check mb-4">
            <input type="checkbox" class="form-check-input" id="remember" name="remember">
            <label class="form-check-label small" for="remember">Se souvenir de moi</label>
        </div>

        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-box-arrow-in-right me-1"></i>Se connecter
        </button>
    </form>

    <p class="auth-note mb-0">
        Les comptes sont créés par le superviseur.<br>
        Aucune inscription publique n’est disponible.
    </p>
@endsection
