<?php

namespace App\Services;

use App\Models\ActivityEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Record an academic event for a student.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        User $student,
        string $type,
        string $title,
        ?string $description = null,
        ?Model $subject = null,
        array $metadata = [],
        ?User $actor = null,
    ): ActivityEvent {
        $actor ??= Auth::user();

        return ActivityEvent::create([
            'student_id' => $student->id,
            'actor_id' => $actor?->id,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}
