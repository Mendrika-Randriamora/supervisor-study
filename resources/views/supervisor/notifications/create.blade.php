@extends('layouts.app')

@section('title', 'Envoyer un message')
@section('topbar-title', 'Envoyer un message')

@section('content')
    <x-page-header title="Envoyer un message" subtitle="Diffusez une notification interne aux étudiants.">
        <x-slot:actions>
            <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-bell me-1"></i>Mes notifications</a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header">Contenu du message</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('supervisor.notifications.store') }}">
                        @csrf

                        <x-field name="title" label="Titre" placeholder="Rappel : échéance vendredi" required :value="old('title')" />

                        <div class="mb-3">
                            <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea name="message" id="message" rows="6" class="form-control @error('message') is-invalid @enderror" required placeholder="Détaillez votre message…">{{ old('message') }}</textarea>
                            @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <span class="form-label d-block">Destinataires <span class="text-danger">*</span></span>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="audience" id="audience-all" value="all" @checked(old('audience', 'all') === 'all') data-audience>
                                <label class="form-check-label" for="audience-all">Tous les étudiants actifs <span class="text-muted text-tiny">({{ $students->count() }})</span></label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="audience" id="audience-selected" value="selected" @checked(old('audience') === 'selected') data-audience>
                                <label class="form-check-label" for="audience-selected">Sélection d’étudiants</label>
                            </div>
                            @error('audience')<div class="text-danger text-tiny mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div id="studentSelection" class="mb-3 {{ old('audience') === 'selected' ? '' : 'd-none' }}">
                            <div class="soft-panel">
                                @if ($students->isEmpty())
                                    <p class="small text-muted mb-0">Aucun étudiant actif disponible.</p>
                                @else
                                    <div class="d-grid gap-2" style="max-height: 300px; overflow-y: auto">
                                        @foreach ($students as $student)
                                            @php $checked = in_array($student->id, old('student_ids', []), true); @endphp
                                            <label class="d-flex align-items-center gap-2 small" for="notif-student-{{ $student->id }}">
                                                <input class="form-check-input mt-0" type="checkbox" name="student_ids[]" id="notif-student-{{ $student->id }}" value="{{ $student->id }}" @checked($checked)>
                                                <span class="avatar" style="width:26px;height:26px;font-size:.68rem">{{ $student->initials() }}</span>
                                                <span class="flex-grow-1">{{ $student->name }}</span>
                                                <span class="text-muted text-tiny">{{ $student->email }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            @error('student_ids')<div class="text-danger text-tiny mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Envoyer la notification</button>
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card">
                <div class="card-header">Bon à savoir</div>
                <div class="card-body">
                    <ul class="small text-muted ps-3 mb-0">
                        <li class="mb-2">Les notifications sont internes à la plateforme : aucun e-mail n’est envoyé.</li>
                        <li class="mb-2">Chaque étudiant reçoit une notification personnelle avec un compteur non lu.</li>
                        <li>Les étudiants désactivés ne reçoivent rien.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const radios = Array.from(document.querySelectorAll('[data-audience]'));
            const selection = document.getElementById('studentSelection');
            if (!selection) return;

            const sync = () => {
                const selected = radios.find((radio) => radio.checked)?.value === 'selected';
                selection.classList.toggle('d-none', !selected);
            };

            radios.forEach((radio) => radio.addEventListener('change', sync));
            sync();
        });
    </script>
@endpush
