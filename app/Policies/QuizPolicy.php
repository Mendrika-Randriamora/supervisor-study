<?php

namespace App\Policies;

use App\Enums\QuizStatus;
use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSupervisor() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Quiz $quiz): bool
    {
        return $quiz->status === QuizStatus::Published
            && $quiz->published_at !== null
            && $quiz->published_at->isPast();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return false;
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return false;
    }

    public function attempt(User $user, Quiz $quiz): bool
    {
        return $user->isStudent() && $this->view($user, $quiz) && $quiz->questions()->exists();
    }
}
