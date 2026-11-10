<?php

namespace App\Policies;

use App\Enums\ExerciseStatus;
use App\Models\Exercise;
use App\Models\User;

class ExercisePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSupervisor() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Exercise $exercise): bool
    {
        return $exercise->status === ExerciseStatus::Published
            && $exercise->published_at !== null
            && $exercise->published_at->isPast();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Exercise $exercise): bool
    {
        return false;
    }

    public function delete(User $user, Exercise $exercise): bool
    {
        return false;
    }
}
