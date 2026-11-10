<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['quiz_attempt_id', 'quiz_question_id', 'points_awarded', 'is_correct'])]
class QuizAnswer extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'points_awarded' => 'decimal:2',
            'is_correct' => 'boolean',
        ];
    }

    /** @return BelongsTo<QuizAttempt, $this> */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    /** @return BelongsTo<QuizQuestion, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }

    /** @return BelongsToMany<QuizOption, $this> */
    public function selectedOptions(): BelongsToMany
    {
        return $this->belongsToMany(
            QuizOption::class,
            'quiz_answer_options',
            'quiz_answer_id',
            'quiz_option_id'
        )->withTimestamps();
    }
}
