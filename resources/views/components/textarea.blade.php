@props(['name', 'label', 'value' => null, 'placeholder' => null, 'rows' => 4, 'required' => false, 'help' => null, 'noCopy' => false])

<div class="mb-3">
    <label for="{{ $name }}" class="form-label">{{ $label }} @if ($required)<span class="text-danger">*</span>@endif</label>
    <textarea
        name="{{ $name }}"
        id="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        @if ($required) required @endif
        @if ($noCopy) class="form-control no-copy @error($name) is-invalid @enderror" data-no-copy @else {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }} @endif
    >{{ old($name, $value) }}</textarea>
    @if ($noCopy)
        @error($name)
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    @else
        @error($name)
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    @endif
    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
