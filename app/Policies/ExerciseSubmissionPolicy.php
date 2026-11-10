<?php

namespace App\Policies;

use App\Models\ExerciseSubmission;
use App\Models\User;

class ExerciseSubmissionPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSupervisor() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, ExerciseSubmission $submission): bool
    {
        return $user->isStudent() && $submission->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isStudent();
    }

    public function update(User $user, ExerciseSubmission $submission): bool
    {
        return false;
    }

    public function delete(User $user, ExerciseSubmission $submission): bool
    {
        return false;
    }

    public function grade(User $user, ExerciseSubmission $submission): bool
    {
        return false;
    }
}
