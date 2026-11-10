@php
    $task = $task ?? null;
    $assignedIds = old('student_ids', $task ? $task->assignments->pluck('user_id')->all() : []);
    $assignedIds = collect($assignedIds)->map(fn ($id) => (int) $id)->all();
@endphp

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Veuillez corriger les erreurs suivantes :</div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-12 col-xl-8">
        <div class="card mb-3">
            <div class="card-header">Informations de la tâche</div>
            <div class="card-body">
                <x-field name="title" label="Titre" :value="$task?->title" placeholder="Réviser le chapitre sur la dérivation" required />

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror" placeholder="Consignes, ressources, travail attendu…">{{ old('description', $task?->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <label for="subject_id" class="form-label">Matière <span class="text-danger">*</span></label>
                        <select name="subject_id" id="subject_id" class="form-select @error('subject_id') is-invalid @enderror" required data-task-subject>
                            <option value="">Sélectionner…</option>
                            @foreach ($subjects as $subjectItem)
                                <option value="{{ $subjectItem->id }}" @selected((int) old('subject_id', $task?->subject_id) === $subjectItem->id)>{{ $subjectItem->name }}</option>
                            @endforeach
                        </select>
                        @error('subject_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label for="theme_id" class="form-label">Thème (facultatif)</label>
                        <select name="theme_id" id="theme_id" class="form-select @error('theme_id') is-invalid @enderror" data-task-theme>
                            <option value="">Aucun thème particulier</option>
                            @foreach ($themes as $theme)
                                <option value="{{ $theme->id }}" data-subject="{{ $theme->subject_id }}" @selected((int) old('theme_id', $task?->theme_id) === $theme->id)>{{ $theme->subject?->name }} — {{ $theme->name }}</option>
                            @endforeach
                        </select>
                        @error('theme_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <label for="exercise_id" class="form-label">Exercice associé (facultatif)</label>
                        <select name="exercise_id" id="exercise_id" class="form-select @error('exercise_id') is-invalid @enderror" data-task-exercise>
                            <option value="">Aucun</option>
                            @foreach ($exercises as $exercise)
                                <option value="{{ $exercise->id }}" data-subject="{{ $exercise->theme?->subject_id }}" @selected((int) old('exercise_id', $task?->exercise_id) === $exercise->id)>{{ $exercise->title }}</option>
                            @endforeach
                        </select>
                        @error('exercise_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label for="quiz_id" class="form-label">Quiz associé (facultatif)</label>
                        <select name="quiz_id" id="quiz_id" class="form-select @error('quiz_id') is-invalid @enderror" data-task-quiz>
                            <option value="">Aucun</option>
                            @foreach ($quizzes as $quiz)
                                <option value="{{ $quiz->id }}" data-subject="{{ $quiz->theme?->subject_id }}" @selected((int) old('quiz_id', $task?->quiz_id) === $quiz->id)>{{ $quiz->title }}</option>
                            @endforeach
                        </select>
                        @error('quiz_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <p class="form-text mt-0">Une tâche ne peut être liée qu’à un exercice ou à un quiz, pas aux deux à la fois.</p>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card mb-3">
            <div class="card-header">Planification</div>
            <div class="card-body">
                <x-field name="starts_at" type="date" label="Date de début" :value="$task?->starts_at?->format('Y-m-d') ?? now()->format('Y-m-d')" required />
                <x-field name="due_at" type="datetime-local" label="Échéance (facultatif)" :value="$task?->due_at?->format('Y-m-d\TH:i')" help="Laissez vide pour une tâche sans date limite." />

                <div class="mb-0">
                    <label for="priority" class="form-label">Priorité <span class="text-danger">*</span></label>
                    <select name="priority" id="priority" class="form-select @error('priority') is-invalid @enderror" required>
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority->value }}" @selected(old('priority', $task?->priority?->value ?? 'normal') === $priority->value)>{{ $priority->label() }}</option>
                        @endforeach
                    </select>
                    @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Étudiants concernés <span class="text-danger">*</span></span>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-select-all-students>Tous</button>
            </div>
            <div class="card-body">
                @error('student_ids')<div class="alert alert-danger py-2 small">{{ $message }}</div>@enderror
                @if ($students->isEmpty())
                    <p class="small text-muted mb-0">Aucun étudiant actif. Créez d’abord un compte étudiant.</p>
                @else
                    <div class="d-grid gap-2" style="max-height: 280px; overflow-y: auto">
                        @foreach ($students as $student)
                            <label class="d-flex align-items-center gap-2 small" for="student-{{ $student->id }}">
                                <input class="form-check-input mt-0" type="checkbox" name="student_ids[]" id="student-{{ $student->id }}" value="{{ $student->id }}" @checked(in_array($student->id, $assignedIds, true)) data-student-checkbox>
                                <span class="avatar" style="width:26px;height:26px;font-size:.68rem">{{ $student->initials() }}</span>
                                <span class="flex-grow-1">{{ $student->name }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif
                @error('student_ids.*')<div class="text-danger text-tiny mt-1">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mt-3">
    <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>{{ $submitLabel }}</button>
    <a href="{{ route('supervisor.tasks.index') }}" class="btn btn-outline-secondary">Annuler</a>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const subjectSelect = document.querySelector('[data-task-subject]');
            const filterable = [
                document.querySelector('[data-task-theme]'),
                document.querySelector('[data-task-exercise]'),
                document.querySelector('[data-task-quiz]'),
            ].filter(Boolean);

            const applyFilter = () => {
                const subjectId = subjectSelect?.value;
                filterable.forEach((select) => {
                    Array.from(select.options).forEach((option) => {
                        if (!option.dataset.subject) return;
                        const visible = !subjectId || option.dataset.subject === subjectId;
                        option.hidden = !visible;
                        option.disabled = !visible;
                        if (!visible && option.selected) {
                            select.value = '';
                        }
                    });
                });
            };

            if (subjectSelect) {
                subjectSelect.addEventListener('change', applyFilter);
                applyFilter();
            }

            document.querySelector('[data-select-all-students]')?.addEventListener('click', (event) => {
                const boxes = Array.from(document.querySelectorAll('[data-student-checkbox]'));
                const shouldCheck = boxes.some((box) => !box.checked);
                boxes.forEach((box) => { box.checked = shouldCheck; });
                event.currentTarget.textContent = shouldCheck ? 'Aucun' : 'Tous';
            });
        });
    </script>
@endpush
