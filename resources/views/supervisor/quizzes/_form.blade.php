<div class="row g-3">
    <div class="col-12">
        <x-field name="title" label="Titre du quiz" :required="true" placeholder="Quiz — Probabilités conditionnelles" />
    </div>
    <div class="col-12">
        <x-textarea name="description" label="Description" :rows="3" placeholder="Objectifs et consignes du quiz (facultatif)" />
    </div>

    <div class="col-12 col-md-6">
        <label for="subject_select" class="form-label">Matière <span class="text-danger">*</span></label>
        <select id="subject_select" class="form-select">
            <option value="">Choisir une matière</option>
            @foreach ($subjects as $subject)
                <option value="{{ $subject->id }}" @selected(old('subject_id', $quiz->theme?->subject_id ?? null) == $subject->id)>{{ $subject->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6">
        <label for="theme_id" class="form-label">Thème <span class="text-danger">*</span></label>
        <select id="theme_id" name="theme_id" class="form-select @error('theme_id') is-invalid @enderror" required>
            <option value="">Choisir un thème</option>
            @foreach ($themes as $theme)
                <option value="{{ $theme->id }}" data-subject="{{ $theme->subject_id }}" @selected(old('theme_id', $quiz->theme_id ?? null) == $theme->id)>
                    {{ $theme->subject?->name }} — {{ $theme->name }}
                </option>
            @endforeach
        </select>
        @error('theme_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-6 col-md-4">
        <x-field name="time_limit_minutes" label="Durée indicative" type="number" :value="$quiz->time_limit_minutes ?? ''" help="Minutes (facultatif)." />
    </div>

    <div class="col-6 col-md-4">
        <label for="published_at" class="form-label">Date de publication</label>
        <input type="datetime-local" id="published_at" name="published_at" class="form-control @error('published_at') is-invalid @enderror"
               value="{{ old('published_at', isset($quiz) && $quiz->published_at ? $quiz->published_at->format('Y-m-d\TH:i') : '') }}">
        @error('published_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="due_at" class="form-label">Date limite (facultative)</label>
        <input type="datetime-local" id="due_at" name="due_at" class="form-control @error('due_at') is-invalid @enderror"
               value="{{ old('due_at', isset($quiz) && $quiz->due_at ? $quiz->due_at->format('Y-m-d\TH:i') : '') }}">
        @error('due_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    @if (isset($quiz))
        <div class="col-12 col-md-4">
            <label for="status" class="form-label">Statut</label>
            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                @foreach ($statuses as $statusOption)
                    <option value="{{ $statusOption->value }}" @selected(old('status', $quiz->status->value) === $statusOption->value)>{{ $statusOption->label() }}</option>
                @endforeach
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">La publication exige au moins une question.</div>
        </div>
    @else
        <input type="hidden" name="status" value="draft">
    @endif
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
                    option.hidden = subjectId && option.dataset.subject !== subjectId;
                });
                const selected = themeSelect.selectedOptions[0];
                if (selected && selected.hidden) themeSelect.value = '';
            };

            subjectSelect.addEventListener('change', filterThemes);
            filterThemes();
        });
    </script>
@endpush
