@props(['title', 'subtitle' => null])

<div {{ $attributes->class(['page-header']) }}>
    <div>
        <h1 class="page-title">{{ $title }}</h1>
        @if ($subtitle)
            <p class="page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="page-actions">{{ $actions }}</div>
    @endif
</div>
