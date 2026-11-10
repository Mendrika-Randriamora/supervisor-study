<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'code' => null,
            'description' => fake()->sentence(),
            'color' => fake()->randomElement(['#1e4d6b', '#2f6b4f', '#7a3e65', '#7a5c1e']),
            'position' => 0,
            'archived_at' => null,
        ];
    }
}
