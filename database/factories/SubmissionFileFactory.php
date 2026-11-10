<?php

namespace Database\Factories;

use App\Models\ExerciseSubmission;
use App\Models\SubmissionFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissionFile>
 */
class SubmissionFileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'submission_id' => ExerciseSubmission::factory(),
            'path' => 'submissions/example.jpg',
            'original_name' => 'copie.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ];
    }
}
