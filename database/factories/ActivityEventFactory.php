<?php

namespace Database\Factories;

use App\Models\ActivityEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityEvent>
 */
class ActivityEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => User::factory(),
            'actor_id' => User::factory()->supervisor(),
            'type' => 'exercise_submitted',
            'title' => fake()->sentence(),
            'description' => fake()->sentence(),
            'metadata' => [],
            'occurred_at' => now(),
        ];
    }
}
