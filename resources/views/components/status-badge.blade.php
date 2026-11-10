@props(['status' => null, 'label' => null, 'tone' => null])

@php
    $classes = $tone;

    if ($status instanceof \App\Enums\TaskStatus) {
        $classes = $status->badgeClass();
        $label = $label ?? $status->label();
    } elseif ($status instanceof \App\Enums\ExerciseStatus || $status instanceof \App\Enums\QuizStatus) {
        $classes = $status->badgeClass();
        $label = $label ?? $status->label();
    } elseif ($status instanceof \App\Enums\SubmissionStatus) {
        $classes = $status->badgeClass();
        $label = $label ?? $status->label();
    }
@endphp

<span {{ $attributes->class(['badge', $classes ?: 'text-bg-secondary']) }}>{{ $label }}</span>
