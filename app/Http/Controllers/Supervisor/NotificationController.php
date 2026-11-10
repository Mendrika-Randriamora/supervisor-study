<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendNotificationRequest;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function create(): View
    {
        return view('supervisor.notifications.create', [
            'students' => User::students()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(SendNotificationRequest $request, NotificationService $notifications): RedirectResponse
    {
        $students = $request->validated('audience') === 'all'
            ? User::students()->active()->get()
            : User::students()->active()->whereIn('id', $request->validated('student_ids'))->get();

        $notifications->manual($students, $request->validated('title'), $request->validated('message'));

        return back()->with('success', 'Notification envoyée à '.$students->count().' étudiant(s).');
    }
}
