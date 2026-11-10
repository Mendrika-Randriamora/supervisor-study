<?php

namespace Database\Factories;

use App\Enums\QuizQuestionType;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizQuestion>
 */
class QuizQuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'question_text' => fake()->sentence().' ?',
            'type' => QuizQuestionType::Single,
            'points' => 1,
            'explanation' => fake()->sentence(),
            'position' => 0,
        ];
    }

    public function multiple(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => QuizQuestionType::Multiple,
        ]);
    }
}
