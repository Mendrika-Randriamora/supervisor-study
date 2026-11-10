@extends('layouts.app')

@section('title', 'Nouvelle tâche')
@section('topbar-title', 'Nouvelle tâche')

@section('content')
    <x-page-header title="Nouvelle tâche" subtitle="Attribuez un travail à un ou plusieurs étudiants.">
        <x-slot:actions>
            <a href="{{ route('supervisor.tasks.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ route('supervisor.tasks.store') }}">
        @csrf
        @include('supervisor.tasks._form', ['submitLabel' => 'Créer la tâche'])
    </form>
@endsection
