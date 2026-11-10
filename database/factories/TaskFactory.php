<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Models\Subject;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'created_by' => User::factory()->supervisor(),
            'subject_id' => Subject::factory(),
            'theme_id' => null,
            'exercise_id' => null,
            'quiz_id' => null,
            'title' => ucfirst(fake()->sentence(4)),
            'description' => fake()->paragraph(),
            'starts_at' => now()->toDateString(),
            'due_at' => now()->addWeek(),
            'priority' => TaskPriority::Normal,
        ];
    }
}
