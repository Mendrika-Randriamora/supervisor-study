@extends('layouts.guest')

@section('title', 'Page introuvable')

@section('content')
    <div class="text-center py-3">
        <i class="bi bi-compass text-muted" style="font-size:2.2rem"></i>
        <h1 class="h4 mt-3">Page introuvable</h1>
        <p class="text-muted small">La ressource demandée n’existe pas ou n’est plus accessible.</p>
        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn btn-primary mt-2">Retour au tableau de bord</a>
    </div>
@endsection
