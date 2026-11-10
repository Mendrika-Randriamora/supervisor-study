<?php

namespace Database\Factories;

use App\Models\QuizAnswer;
use App\Models\QuizAnswerOption;
use App\Models\QuizOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAnswerOption>
 */
class QuizAnswerOptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quiz_answer_id' => QuizAnswer::factory(),
            'quiz_option_id' => QuizOption::factory(),
        ];
    }
}
