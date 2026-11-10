@extends('layouts.app')

@section('title', 'Modifier '.$exercise->title)
@section('topbar-title', 'Modifier un exercice')

@section('content')
    <x-page-header :title="'Modifier : '.$exercise->title" subtitle="La modification d’un exercice publié ne renvoie pas de notification automatique." />

    <div class="row g-3">
        <div class="col-12 col-xl-9">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('supervisor.exercises.update', $exercise) }}" enctype="multipart/form-data">
                        @csrf
                        @method('patch')
                        @include('supervisor.exercises._form')
                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Enregistrer</button>
                            <a href="{{ route('supervisor.exercises.show', $exercise) }}" class="btn btn-outline-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
