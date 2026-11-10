<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSupervisor() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Task $task): bool
    {
        return $user->isStudent() && $task->assignmentFor($user) !== null;
    }

    public function update(User $user, Task $task): bool
    {
        return $user->isStudent() && $task->assignmentFor($user) !== null;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, Task $task): bool
    {
        return false;
    }
}
