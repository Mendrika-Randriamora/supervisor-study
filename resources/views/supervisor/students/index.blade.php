@extends('layouts.app')

@section('title', 'Étudiants')
@section('topbar-title', 'Gestion des étudiants')

@section('content')
    <x-page-header title="Étudiants" subtitle="Créez, recherchez et suivez les comptes étudiants.">
        <x-slot:actions>
            <a href="{{ route('supervisor.students.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-person-plus me-1"></i>Nouvel étudiant</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('supervisor.students.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-6">
                    <label class="form-label" for="q">Rechercher</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="search" id="q" name="q" value="{{ $search }}" class="form-control" placeholder="Nom ou adresse e-mail">
                    </div>
                </div>
                <div class="col-8 col-md-4">
                    <label class="form-label" for="status">Statut</label>
                    <select id="status" name="status" class="form-select" data-auto-submit>
                        <option value="">Tous</option>
                        <option value="active" @selected($status === 'active')>Actifs</option>
                        <option value="inactive" @selected($status === 'inactive')>Désactivés</option>
                    </select>
                </div>
                <div class="col-4 col-md-2">
                    <button class="btn btn-outline-primary w-100" type="submit">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>{{ $students->total() }} étudiant(s)</span>
            <span class="text-muted text-tiny">Page {{ $students->currentPage() }} / {{ $students->lastPage() }}</span>
        </div>
        <div class="card-body p-0">
            @if ($students->isEmpty())
                <x-empty-state icon="bi-people" title="Aucun étudiant trouvé" text="Modifiez votre recherche ou créez un nouveau compte étudiant.">
                    <a href="{{ route('supervisor.students.create') }}" class="btn btn-primary btn-sm">Créer un étudiant</a>
                </x-empty-state>
            @else
                <div class="table-responsive table-desktop">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>Étudiant</th><th>Statut</th><th>Soumissions</th><th>Quiz</th><th>Dernière connexion</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($students as $student)
                                <tr class="hover-row">
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar">{{ $student->initials() }}</span>
                                            <div>
                                                <a href="{{ route('supervisor.students.show', $student) }}" class="fw-semibold text-reset">{{ $student->name }}</a>
                                                <div class="text-muted text-tiny">{{ $student->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if ($student->is_active)
                                            <span class="badge text-bg-success">Actif</span>
                                        @else
                                            <span class="badge text-bg-danger">Désactivé</span>
                                        @endif
                                    </td>
                                    <td class="mono-num">{{ $student->submissions_count }}</td>
                                    <td class="mono-num">{{ $student->quiz_attempts_count }}</td>
                                    <td class="text-muted text-tiny">{{ $student->last_login_at?->format('d/m/Y H:i') ?? 'Jamais' }}</td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="{{ route('supervisor.students.show', $student) }}" class="btn btn-sm btn-outline-primary">Suivi</a>
                                            <a href="{{ route('supervisor.students.edit', $student) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mobile-list p-2">
                    @foreach ($students as $student)
                        <div class="mobile-list-item">
                            <div class="d-flex align-items-start gap-2">
                                <span class="avatar">{{ $student->initials() }}</span>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between gap-2">
                                        <a href="{{ route('supervisor.students.show', $student) }}" class="fw-semibold small text-reset">{{ $student->name }}</a>
                                        @if ($student->is_active)
                                            <span class="badge text-bg-success">Actif</span>
                                        @else
                                            <span class="badge text-bg-danger">Désactivé</span>
                                        @endif
                                    </div>
                                    <div class="text-muted text-tiny">{{ $student->email }}</div>
                                    <div class="meta-line mt-1">
                                        <span>{{ $student->submissions_count }} soumission(s)</span>
                                        <span>{{ $student->quiz_attempts_count }} quiz</span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 mt-2">
                                <a href="{{ route('supervisor.students.show', $student) }}" class="btn btn-sm btn-outline-primary flex-grow-1">Suivi</a>
                                <a href="{{ route('supervisor.students.edit', $student) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="p-3">{{ $students->links() }}</div>
            @endif
        </div>
    </div>
@endsection
