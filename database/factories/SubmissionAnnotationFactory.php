<?php

namespace Database\Factories;

use App\Enums\AnnotationType;
use App\Models\SubmissionAnnotation;
use App\Models\SubmissionFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissionAnnotation>
 */
class SubmissionAnnotationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'submission_file_id' => SubmissionFile::factory(),
            'created_by' => User::factory()->supervisor(),
            'type' => AnnotationType::Overlay,
            'data' => ['paths' => [], 'rects' => [], 'comments' => []],
            'comment' => fake()->sentence(),
        ];
    }
}
