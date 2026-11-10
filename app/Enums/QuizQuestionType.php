<?php

namespace App\Enums;

enum QuizQuestionType: string
{
    case Single = 'single';
    case Multiple = 'multiple';

    public function label(): string
    {
        return match ($this) {
            self::Single => 'Choix unique',
            self::Multiple => 'Choix multiples',
        };
    }
}
