@php
    $editing = isset($question) && $question;
    $optionValues = $editing
        ? $question->options->map(fn ($option) => ['text' => $option->option_text, 'is_correct' => $option->is_correct])->values()->all()
        : [['text' => '', 'is_correct' => false], ['text' => '', 'is_correct' => false], ['text' => '', 'is_correct' => false], ['text' => '', 'is_correct' => false]];
@endphp

<form method="POST" action="{{ $action }}" class="question-form">
    @csrf
    @if (($method ?? 'post') !== 'post')
        @method($method)
    @endif

    <div class="mb-2">
        <label class="form-label">Question <span class="text-danger">*</span></label>
        <textarea name="question_text" rows="2" class="form-control" required placeholder="Énoncé de la question">{{ old('question_text', $question->question_text ?? '') }}</textarea>
    </div>

    <div class="row g-2 mb-2">
        <div class="col-12 col-md-5">
            <label class="form-label">Type</label>
            <select name="type" class="form-select" required>
                @foreach (\App\Enums\QuizQuestionType::cases() as $questionType)
                    <option value="{{ $questionType->value }}" @selected(old('type', $question->type->value ?? 'single') === $questionType->value)>{{ $questionType->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label">Points</label>
            <input type="number" step="0.25" min="0.01" max="100" name="points" class="form-control" value="{{ old('points', $question->points ?? 1) }}" required>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Ordre</label>
            <input type="number" min="0" max="999" name="position" class="form-control" value="{{ old('position', $question->position ?? '') }}" placeholder="Automatique">
        </div>
    </div>

    <div class="mb-2">
        <label class="form-label d-flex justify-content-between align-items-center">
            <span>Propositions <span class="text-danger">*</span></span>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-add-option><i class="bi bi-plus-lg me-1"></i>Ajouter</button>
        </label>
        <p class="form-text mt-0 mb-2">Cochez la ou les bonnes réponses. Choix unique : exactement une. Choix multiples : au moins deux, avec au moins une proposition incorrecte.</p>
        <div class="question-options" data-options-container>
            @foreach ($optionValues as $index => $option)
                <div class="input-group mb-2 option-row">
                    <span class="input-group-text" title="Bonne réponse">
                        <input class="form-check-input mt-0" type="checkbox" name="options[{{ $index }}][is_correct]" value="1" @checked($option['is_correct'] ?? false) aria-label="Bonne réponse">
                    </span>
                    <input type="text" name="options[{{ $index }}][text]" value="{{ $option['text'] }}" class="form-control" placeholder="Proposition {{ $index + 1 }}" required>
                    <button type="button" class="btn btn-outline-danger" data-remove-option aria-label="Supprimer"><i class="bi bi-x-lg"></i></button>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mb-2">
        <label class="form-label">Explication affichée après soumission</label>
        <textarea name="explanation" rows="2" class="form-control" placeholder="Facultatif">{{ old('explanation', $question->explanation ?? '') }}</textarea>
    </div>

    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check2 me-1"></i>{{ $submitLabel ?? 'Enregistrer la question' }}</button>
</form>
