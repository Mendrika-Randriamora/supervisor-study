<?php

namespace Database\Factories;

use App\Enums\ExerciseStatus;
use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exercise>
 */
class ExerciseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'theme_id' => Theme::factory(),
            'created_by' => User::factory()->supervisor(),
            'title' => ucfirst(fake()->sentence(4)),
            'description' => fake()->paragraph(),
            'type' => ExerciseType::Text,
            'max_points' => 20,
            'published_at' => null,
            'due_at' => null,
            'status' => ExerciseStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExerciseStatus::Published,
            'published_at' => now()->subDay(),
        ]);
    }

    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ExerciseType::Pdf,
        ]);
    }

    public function dueSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_at' => now()->addDay(),
        ]);
    }
}
