<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\StatisticsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(StatisticsService $statistics): View
    {
        $user = request()->user();

        if ($user->isSupervisor()) {
            return view('dashboards.supervisor', [
                'overview' => $statistics->supervisorOverview(),
                'students' => User::students()->active()->orderBy('name')->get(),
            ]);
        }

        $overview = $statistics->studentOverview($user);

        return view('dashboards.student', [
            'overview' => $overview,
            'notifications' => $user->notifications()->latest()->limit(5)->get(),
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }
}
