<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use Database\Factories\ExerciseSubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['exercise_id', 'user_id', 'version', 'status', 'revision_comment', 'text_answer', 'submitted_at', 'is_late', 'graded_at'])]
class ExerciseSubmission extends Model
{
    /** @use HasFactory<ExerciseSubmissionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => SubmissionStatus::class,
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
            'is_late' => 'boolean',
        ];
    }

    /** @return BelongsTo<Exercise, $this> */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<SubmissionFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(SubmissionFile::class, 'submission_id');
    }

    /** @return HasMany<ExerciseGrade, $this> */
    public function grades(): HasMany
    {
        return $this->hasMany(ExerciseGrade::class, 'submission_id')->latest('graded_at');
    }

    /** @return HasOne<ExerciseGrade, $this> */
    public function latestGrade(): HasOne
    {
        return $this->hasOne(ExerciseGrade::class, 'submission_id')->latestOfMany('graded_at');
    }

    public function isGraded(): bool
    {
        return $this->status === SubmissionStatus::Graded;
    }
}
