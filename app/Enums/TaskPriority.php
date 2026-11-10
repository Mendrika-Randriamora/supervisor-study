<?php

namespace App\Enums;

enum TaskPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Basse',
            self::Normal => 'Normale',
            self::High => 'Haute',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Low => 'text-bg-secondary',
            self::Normal => 'text-bg-primary',
            self::High => 'text-bg-danger',
        };
    }
}
