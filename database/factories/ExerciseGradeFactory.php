<?php

namespace Database\Factories;

use App\Models\ExerciseGrade;
use App\Models\ExerciseSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExerciseGrade>
 */
class ExerciseGradeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'submission_id' => ExerciseSubmission::factory(),
            'graded_by' => User::factory()->supervisor(),
            'score' => fake()->randomFloat(2, 0, 20),
            'max_points' => 20,
            'comment' => fake()->sentence(),
            'graded_at' => now(),
        ];
    }
}
