<?php

namespace App\Models;

use App\Enums\QuizStatus;
use Database\Factories\QuizFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['theme_id', 'created_by', 'title', 'description', 'total_points', 'time_limit_minutes', 'published_at', 'due_at', 'status'])]
class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => QuizStatus::class,
            'total_points' => 'decimal:2',
            'published_at' => 'datetime',
            'due_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Theme, $this> */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<QuizQuestion, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('position')->orderBy('id');
    }

    /** @return HasManyThrough<QuizOption, QuizQuestion, $this> */
    public function options(): HasManyThrough
    {
        return $this->hasManyThrough(QuizOption::class, QuizQuestion::class);
    }

    /** @return HasMany<QuizAttempt, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function attemptsFor(User $student): HasMany
    {
        return $this->attempts()->where('user_id', $student->id);
    }

    public function bestAttemptFor(User $student): ?QuizAttempt
    {
        return $this->attemptsFor($student)->orderByDesc('score')->orderByDesc('submitted_at')->first();
    }

    public function latestAttemptFor(User $student): ?QuizAttempt
    {
        return $this->attemptsFor($student)->latest('submitted_at')->first();
    }

    public function isOverdue(): bool
    {
        return $this->due_at !== null && $this->due_at->isPast();
    }

    public function recalculateTotalPoints(): void
    {
        $this->forceFill(['total_points' => $this->questions()->sum('points')])->save();
    }

    public function hasAttempts(): bool
    {
        return $this->attempts()->exists();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', QuizStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
