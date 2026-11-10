<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['submission_id', 'path', 'original_name', 'mime_type', 'size'])]
class SubmissionFile extends Model
{
    use HasFactory;

    /** @return BelongsTo<ExerciseSubmission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(ExerciseSubmission::class, 'submission_id');
    }

    /** @return HasMany<SubmissionAnnotation, $this> */
    public function annotations(): HasMany
    {
        return $this->hasMany(SubmissionAnnotation::class);
    }
}
