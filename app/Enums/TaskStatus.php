<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Overdue = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::Todo => 'À faire',
            self::InProgress => 'En cours',
            self::Completed => 'Terminée',
            self::Overdue => 'En retard',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Todo => 'text-bg-secondary',
            self::InProgress => 'text-bg-primary',
            self::Completed => 'text-bg-success',
            self::Overdue => 'text-bg-danger',
        };
    }

    public function isPersisted(): bool
    {
        return $this !== self::Overdue;
    }
}
