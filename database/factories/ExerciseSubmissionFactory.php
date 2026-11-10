<?php

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Models\Exercise;
use App\Models\ExerciseSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExerciseSubmission>
 */
class ExerciseSubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'exercise_id' => Exercise::factory(),
            'user_id' => User::factory(),
            'version' => 1,
            'status' => SubmissionStatus::Submitted,
            'text_answer' => fake()->paragraph(),
            'submitted_at' => now(),
            'is_late' => false,
            'graded_at' => null,
        ];
    }
}
