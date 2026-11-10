<?php

namespace App\Models;

use App\Enums\AnnotationType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['submission_file_id', 'created_by', 'type', 'data', 'comment'])]
class SubmissionAnnotation extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => AnnotationType::class,
            'data' => 'array',
        ];
    }

    /** @return BelongsTo<SubmissionFile, $this> */
    public function file(): BelongsTo
    {
        return $this->belongsTo(SubmissionFile::class, 'submission_file_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
