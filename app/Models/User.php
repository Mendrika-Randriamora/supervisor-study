<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'phone', 'notes', 'last_login_at', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function isSupervisor(): bool
    {
        return $this->role === UserRole::Supervisor;
    }

    public function isStudent(): bool
    {
        return $this->role === UserRole::Student;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/u', trim($this->name)) ?: [];
        $initials = collect($parts)->filter()->take(2)->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');

        return $initials !== '' ? $initials : '?';
    }

    /** @return HasMany<ExerciseSubmission, $this> */
    public function submissions(): HasMany
    {
        return $this->hasMany(ExerciseSubmission::class);
    }

    /** @return HasMany<QuizAttempt, $this> */
    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /** @return HasMany<TaskAssignment, $this> */
    public function taskAssignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    /** @return HasMany<ActivityEvent, $this> */
    public function activityEvents(): HasMany
    {
        return $this->hasMany(ActivityEvent::class, 'student_id');
    }

    /** @return HasMany<Exercise, $this> */
    public function createdExercises(): HasMany
    {
        return $this->hasMany(Exercise::class, 'created_by');
    }

    /** @return HasMany<Task, $this> */
    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function scopeStudents($query)
    {
        return $query->where('role', UserRole::Student);
    }

    public function scopeSupervisors($query)
    {
        return $query->where('role', UserRole::Supervisor);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
