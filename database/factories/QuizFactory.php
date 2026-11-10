<?php

namespace Database\Factories;

use App\Enums\QuizStatus;
use App\Models\Quiz;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    public function definition(): array
    {
        return [
            'theme_id' => Theme::factory(),
            'created_by' => User::factory()->supervisor(),
            'title' => ucfirst(fake()->sentence(4)),
            'description' => fake()->paragraph(),
            'total_points' => 0,
            'time_limit_minutes' => null,
            'published_at' => null,
            'due_at' => null,
            'status' => QuizStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuizStatus::Published,
            'published_at' => now()->subDay(),
        ]);
    }
}
