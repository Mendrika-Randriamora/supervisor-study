@extends('layouts.app')

@section('title', 'Nouvelle matière')
@section('topbar-title', 'Nouvelle matière')

@section('content')
    <x-page-header title="Nouvelle matière" subtitle="Une matière regroupe plusieurs thèmes, exercices, quiz et tâches." />

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('supervisor.subjects.store') }}">
                        @csrf
                        @include('supervisor.subjects._form', ['subject' => null])
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Créer la matière</button>
                            <a href="{{ route('supervisor.subjects.index') }}" class="btn btn-outline-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
