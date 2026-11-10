<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case Submitted = 'submitted';
    case Graded = 'graded';
    case RevisionRequested = 'revision_requested';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'À corriger',
            self::Graded => 'Corrigé',
            self::RevisionRequested => 'Nouvelle réponse demandée',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Submitted => 'text-bg-warning',
            self::Graded => 'text-bg-success',
            self::RevisionRequested => 'text-bg-info',
        };
    }
}
