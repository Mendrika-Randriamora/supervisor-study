<?php

namespace App\Enums;

enum ExerciseType: string
{
    case Pdf = 'pdf';
    case Text = 'text';

    public function label(): string
    {
        return match ($this) {
            self::Pdf => 'Exercice avec document PDF',
            self::Text => 'Exercice textuel',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Pdf => 'PDF / photos',
            self::Text => 'Textuel',
        };
    }
}
