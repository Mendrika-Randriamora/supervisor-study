<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\ThemeRequest;
use App\Models\Subject;
use App\Models\Theme;
use Illuminate\Http\RedirectResponse;

class ThemeController extends Controller
{
    public function store(ThemeRequest $request, Subject $subject): RedirectResponse
    {
        $data = $request->validated();
        $data['position'] ??= 0;
        $subject->themes()->create($data);

        return redirect()->route('supervisor.subjects.show', $subject)->with('success', 'Thème ajouté.');
    }

    public function update(ThemeRequest $request, Theme $theme): RedirectResponse
    {
        $theme->update($request->validated());

        return redirect()->route('supervisor.subjects.show', $theme->subject_id)->with('success', 'Thème mis à jour.');
    }

    public function archive(Theme $theme): RedirectResponse
    {
        $theme->forceFill(['archived_at' => now()])->save();

        return back()->with('success', 'Thème archivé.');
    }

    public function unarchive(Theme $theme): RedirectResponse
    {
        $theme->forceFill(['archived_at' => null])->save();

        return back()->with('success', 'Thème réactivé.');
    }

    public function destroy(Theme $theme): RedirectResponse
    {
        if ($theme->exercises()->exists() || $theme->quizzes()->exists() || $theme->tasks()->exists()) {
            return back()->withErrors(['theme' => 'Ce thème contient des activités. Archivez-le pour préserver l’historique.']);
        }

        $subjectId = $theme->subject_id;
        $theme->delete();

        return redirect()->route('supervisor.subjects.show', $subjectId)->with('success', 'Thème supprimé.');
    }
}
