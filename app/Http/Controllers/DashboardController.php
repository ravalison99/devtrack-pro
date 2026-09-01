<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;

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
        $notifications = auth()->user()->notifications;
        return view('dashboard.notifications', compact('notifications'));
    }
}
