<?php

namespace App\Http\Controllers;

use App\Models\ExerciseAttachment;
use App\Models\SubmissionFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileController extends Controller
{
    public function exerciseAttachment(ExerciseAttachment $attachment): BinaryFileResponse
    {
        Gate::authorize('view', $attachment->exercise);

        return $this->inline($attachment->path, $attachment->original_name, $attachment->mime_type);
    }

    public function submissionFile(SubmissionFile $file): BinaryFileResponse
    {
        Gate::authorize('view', $file->submission);

        return $this->inline($file->path, $file->original_name, $file->mime_type);
    }

    private function inline(string $path, string $name, string $mimeType): BinaryFileResponse
    {
        $disk = Storage::disk('local');

        abort_unless($disk->exists($path), 404, 'Fichier introuvable.');

        return response()->file($disk->path($path), [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.addslashes($name).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
