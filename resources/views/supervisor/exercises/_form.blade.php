<div class="row g-3">
    <div class="col-12">
        <x-field name="title" label="Titre de l’exercice" :required="true" placeholder="Exercice 3 — Dérivées et tangentes" />
    </div>
    <div class="col-12">
        <x-textarea name="description" label="Consigne / description" :rows="4" placeholder="Expliquez le travail attendu, les critères et les consignes de rendu." />
    </div>

    <div class="col-12 col-md-6">
        <label for="subject_select" class="form-label">Matière <span class="text-danger">*</span></label>
        <select id="subject_select" class="form-select">
            <option value="">Choisir une matière</option>
            @foreach ($subjects as $subject)
                <option value="{{ $subject->id }}" @selected(old('subject_id', $exercise->theme?->subject_id ?? null) == $subject->id)>{{ $subject->name }}</option>
            @endforeach
        </select>
        <div class="form-text">Sert à filtrer les thèmes disponibles.</div>
    </div>

    <div class="col-12 col-md-6">
        <label for="theme_id" class="form-label">Thème <span class="text-danger">*</span></label>
        <select id="theme_id" name="theme_id" class="form-select @error('theme_id') is-invalid @enderror" required>
            <option value="">Choisir un thème</option>
            @foreach ($themes as $theme)
                <option value="{{ $theme->id }}" data-subject="{{ $theme->subject_id }}" @selected(old('theme_id', $exercise->theme_id ?? null) == $theme->id)>
                    {{ $theme->subject?->name }} — {{ $theme->name }}
                </option>
            @endforeach
        </select>
        @error('theme_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="type" class="form-label">Type d’exercice <span class="text-danger">*</span></label>
        <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
            @foreach ($types as $type)
                <option value="{{ $type->value }}" @selected(old('type', $exercise->type->value ?? 'pdf') === $type->value)>{{ $type->shortLabel() }}</option>
            @endforeach
        </select>
        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">PDF : l’étudiant photographie sa copie. Textuel : réponse saisie en ligne.</div>
    </div>

    <div class="col-6 col-md-4">
        <x-field name="max_points" label="Barème maximum" type="number" :value="$exercise->max_points ?? 20" required help="Ex. 5, 10, 20." />
    </div>

    <div class="col-6 col-md-4">
        <label for="status" class="form-label">Statut <span class="text-danger">*</span></label>
        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
            @foreach ($statuses as $statusOption)
                <option value="{{ $statusOption->value }}" @selected(old('status', $exercise->status->value ?? 'draft') === $statusOption->value)>{{ $statusOption->label() }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="published_at" class="form-label">Date de publication</label>
        <input type="datetime-local" id="published_at" name="published_at" class="form-control @error('published_at') is-invalid @enderror"
               value="{{ old('published_at', isset($exercise) && $exercise->published_at ? $exercise->published_at->format('Y-m-d\TH:i') : '') }}">
        @error('published_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Laissez vide pour utiliser la date du jour lors de la publication.</div>
    </div>

    <div class="col-12 col-md-6">
        <label for="due_at" class="form-label">Date limite (facultative)</label>
        <input type="datetime-local" id="due_at" name="due_at" class="form-control @error('due_at') is-invalid @enderror"
               value="{{ old('due_at', isset($exercise) && $exercise->due_at ? $exercise->due_at->format('Y-m-d\TH:i') : '') }}">
        @error('due_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <div class="soft-panel">
            <label for="document" class="form-label">Document PDF de l’énoncé</label>
            <input type="file" id="document" name="document" accept="application/pdf,.pdf" class="form-control @error('document') is-invalid @enderror">
            @error('document')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">PDF uniquement, 10 Mo maximum. Obligatoire pour un exercice PDF publié.</div>

            @if (isset($exercise) && $exercise->attachments->isNotEmpty())
                <div class="mt-2">
                    <div class="small fw-semibold mb-1">Documents actuels</div>
                    @foreach ($exercise->attachments as $attachment)
                        <div class="d-flex align-items-center justify-content-between gap-2 py-1">
                            <a href="{{ route('files.exercise', $attachment) }}" target="_blank" class="small text-truncate"><i class="bi bi-file-earmark-pdf text-danger me-1"></i>{{ $attachment->original_name }}</a>
                            <form method="POST" action="{{ route('supervisor.exercises.attachments.destroy', $attachment) }}">
                                @csrf @method('delete')
                                <button class="btn btn-sm btn-outline-danger" type="submit" data-confirm="Supprimer ce document ?"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const subjectSelect = document.getElementById('subject_select');
            const themeSelect = document.getElementById('theme_id');
            if (!subjectSelect || !themeSelect) return;

            const filterThemes = () => {
                const subjectId = subjectSelect.value;
                Array.from(themeSelect.options).forEach((option, index) => {
                    if (index === 0) return;
                    const matches = !subjectId || option.dataset.subject === subjectId;
                    option.hidden = !matches;
                });

                const selected = themeSelect.selectedOptions[0];
                if (selected && selected.hidden) {
                    themeSelect.value = '';
                }
            };

            subjectSelect.addEventListener('change', filterThemes);
            filterThemes();
        });
    </script>
@endpush
