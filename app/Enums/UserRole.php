<?php

namespace App\Enums;

enum UserRole: string
{
    case Supervisor = 'supervisor';
    case Student = 'student';

    public function label(): string
    {
        return match ($this) {
            self::Supervisor => 'Superviseur',
            self::Student => 'Étudiant',
        };
    }
}
