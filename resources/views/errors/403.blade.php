@extends('layouts.guest')

@section('title', 'Accès refusé')

@section('content')
    <div class="text-center py-3">
        <i class="bi bi-shield-lock text-danger" style="font-size:2.2rem"></i>
        <h1 class="h4 mt-3">Accès refusé</h1>
        <p class="text-muted small">Vous n’êtes pas autorisé à accéder à cette ressource.</p>
        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn btn-primary mt-2">Retour</a>
    </div>
@endsection
