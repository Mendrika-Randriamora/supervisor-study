<?php

namespace Database\Factories;

use App\Models\Exercise;
use App\Models\ExerciseAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExerciseAttachment>
 */
class ExerciseAttachmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'exercise_id' => Exercise::factory(),
            'path' => 'exercises/example.pdf',
            'original_name' => 'enonce.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ];
    }
}
