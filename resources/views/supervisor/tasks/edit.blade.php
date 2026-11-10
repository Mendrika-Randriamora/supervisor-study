@extends('layouts.app')

@section('title', 'Modifier la tâche')
@section('topbar-title', 'Modifier la tâche')

@section('content')
    <x-page-header title="Modifier la tâche" :subtitle="$task->title">
        <x-slot:actions>
            <a href="{{ route('supervisor.tasks.show', $task) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ route('supervisor.tasks.update', $task) }}">
        @csrf
        @method('patch')
        @include('supervisor.tasks._form', ['submitLabel' => 'Enregistrer les modifications'])
    </form>
@endsection
