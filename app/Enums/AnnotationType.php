<?php

namespace App\Enums;

enum AnnotationType: string
{
    case Overlay = 'overlay';

    public function label(): string
    {
        return match ($this) {
            self::Overlay => 'Annotation',
        };
    }
}
