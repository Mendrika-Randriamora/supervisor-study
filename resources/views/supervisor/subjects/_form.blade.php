<div class="row g-3">
    <div class="col-12 col-md-8">
        <x-field name="name" label="Nom de la matière" :required="true" placeholder="Mathématiques" />
    </div>
    <div class="col-12 col-md-4">
        <x-field name="code" label="Code" placeholder="MATH" help="Facultatif, unique." />
    </div>
    <div class="col-12 col-md-4">
        <label for="color" class="form-label">Couleur</label>
        <input type="color" name="color" id="color" value="{{ old('color', $subject->color ?? '#1e4d6b') }}" class="form-control form-control-color w-100 @error('color') is-invalid @enderror">
        @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-md-4">
        <x-field name="position" label="Ordre d’affichage" type="number" :value="$subject->position ?? 0" help="0 = en premier." />
    </div>
    <div class="col-12">
        <x-textarea name="description" label="Description" :rows="4" placeholder="Contenu et objectifs de la matière (facultatif)" />
    </div>
</div>
