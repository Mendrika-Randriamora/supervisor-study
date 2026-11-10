@props(['icon' => 'bi-inbox', 'title' => 'Aucun élément', 'text' => null])

<div {{ $attributes->class(['empty-state']) }}>
    <i class="bi {{ $icon }}"></i>
    <h6>{{ $title }}</h6>
    @if ($text)
        <p>{{ $text }}</p>
    @endif
    @if (isset($slot) && ! $slot->isEmpty())
        <div class="mt-3">{{ $slot }}</div>
    @endif
</div>
