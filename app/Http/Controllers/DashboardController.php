<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Notifications\DatabaseNotification;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboardService) {}

    public function index()
    {
        $indicateurs = $this->dashboardService->indicateursPour(auth()->user());

        return view('dashboard.index', compact('indicateurs'));
    }

    public function notifications()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(5)->withQueryString();

        return view('dashboard.notifications', compact('notifications'));
    }

    public function deleteNotification(DatabaseNotification $notification)
    {
        $utilisateur = auth()->user();

        abort_unless(
            $notification->notifiable_type === get_class($utilisateur) && $notification->notifiable_id === $utilisateur->id,
            403
        );

        $notification->delete();

        return back()->with('success', 'Notification supprimée.');
    }
}
