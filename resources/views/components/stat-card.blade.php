@props(['label', 'value', 'icon' => 'bi-graph-up', 'tone' => 'primary', 'hint' => null])

<div {{ $attributes->class(['stat-card']) }}>
    <div class="stat-card-top">
        <span class="stat-label">{{ $label }}</span>
        <span class="stat-icon {{ $tone }}"><i class="bi {{ $icon }}"></i></span>
    </div>
    <div class="stat-value">{{ $value }}</div>
    @if ($hint)
        <div class="stat-hint">{{ $hint }}</div>
    @endif
</div>
