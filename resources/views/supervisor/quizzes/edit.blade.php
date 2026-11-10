@extends('layouts.app')

@section('title', 'Modifier '.$quiz->title)
@section('topbar-title', 'Modifier un quiz')

@section('content')
    <x-page-header :title="'Modifier : '.$quiz->title" subtitle="Les questions ne sont modifiables que lorsque le quiz est en brouillon et sans tentative." />

    <div class="row g-3">
        <div class="col-12 col-xl-9">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('supervisor.quizzes.update', $quiz) }}">
                        @csrf
                        @method('patch')
                        @include('supervisor.quizzes._form')
                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Enregistrer</button>
                            <a href="{{ route('supervisor.quizzes.show', $quiz) }}" class="btn btn-outline-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
