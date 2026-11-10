<?php

namespace App\Models;

use App\Enums\ExerciseStatus;
use App\Enums\ExerciseType;
use Database\Factories\ExerciseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['theme_id', 'created_by', 'title', 'description', 'type', 'max_points', 'published_at', 'due_at', 'status'])]
class Exercise extends Model
{
    /** @use HasFactory<ExerciseFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => ExerciseType::class,
            'status' => ExerciseStatus::class,
            'max_points' => 'decimal:2',
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

    /** @return HasMany<ExerciseAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(ExerciseAttachment::class);
    }

    /** @return HasMany<ExerciseSubmission, $this> */
    public function submissions(): HasMany
    {
        return $this->hasMany(ExerciseSubmission::class);
    }

    /** @return HasMany<Task, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function submissionFor(User $student): ?ExerciseSubmission
    {
        return $this->submissions()
            ->where('user_id', $student->id)
            ->latest('version')
            ->first();
    }

    public function isOverdue(): bool
    {
        return $this->due_at !== null && $this->due_at->isPast();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ExerciseStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeVisibleToStudents(Builder $query): Builder
    {
        return $query->published()->with(['theme.subject']);
    }
}
