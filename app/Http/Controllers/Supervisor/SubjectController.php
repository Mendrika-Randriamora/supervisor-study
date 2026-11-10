<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubjectRequest;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(): View
    {
        return view('supervisor.subjects.index', [
            'subjects' => Subject::withCount(['themes', 'exercises', 'quizzes', 'tasks'])
                ->orderBy('position')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('supervisor.subjects.create');
    }

    public function store(SubjectRequest $request): RedirectResponse
    {
        $subject = Subject::create($request->validated());

        return redirect()->route('supervisor.subjects.show', $subject)->with('success', 'Matière ajoutée.');
    }

    public function show(Subject $subject): View
    {
        return view('supervisor.subjects.show', [
            'subject' => $subject->load(['themes' => fn ($query) => $query->withCount(['exercises', 'quizzes'])]),
            'recentExercises' => $subject->exercises()->with('theme')->latest()->limit(8)->get(),
            'recentQuizzes' => $subject->quizzes()->with('theme')->latest()->limit(8)->get(),
        ]);
    }

    public function edit(Subject $subject): View
    {
        return view('supervisor.subjects.edit', compact('subject'));
    }

    public function update(SubjectRequest $request, Subject $subject): RedirectResponse
    {
        $subject->update($request->validated());

        return redirect()->route('supervisor.subjects.show', $subject)->with('success', 'Matière mise à jour.');
    }

    public function archive(Subject $subject): RedirectResponse
    {
        $subject->forceFill(['archived_at' => now()])->save();

        return back()->with('success', 'Matière archivée. Les historiques restent consultables.');
    }

    public function unarchive(Subject $subject): RedirectResponse
    {
        $subject->forceFill(['archived_at' => null])->save();

        return back()->with('success', 'Matière réactivée.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        if ($subject->themes()->exists() || $subject->exercises()->exists() || $subject->quizzes()->exists() || $subject->tasks()->exists()) {
            return back()->withErrors(['subject' => 'Cette matière contient des activités. Archivez-la pour préserver l’historique.']);
        }

        $subject->delete();

        return redirect()->route('supervisor.subjects.index')->with('success', 'Matière supprimée.');
    }
}
