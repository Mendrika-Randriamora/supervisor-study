<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'user_id' => User::factory(),
            'attempt_number' => 1,
            'score' => 10,
            'max_points' => 20,
            'correct_count' => 1,
            'incorrect_count' => 1,
            'duration_seconds' => 300,
            'submitted_at' => now(),
        ];
    }
}
