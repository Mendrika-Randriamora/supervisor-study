<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['submission_id', 'graded_by', 'score', 'max_points', 'comment', 'graded_at'])]
class ExerciseGrade extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'max_points' => 'decimal:2',
            'graded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ExerciseSubmission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(ExerciseSubmission::class, 'submission_id');
    }

    /** @return BelongsTo<User, $this> */
    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function convertedTo20(): float
    {
        if ((float) $this->max_points <= 0) {
            return 0.0;
        }

        return round(((float) $this->score / (float) $this->max_points) * 20, 2);
    }
}
