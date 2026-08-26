<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WeeklyReport;

class WeeklyReportPolicy
{
    public function create(User $user): bool
    {
        return $user->isStagiaire();
    }

    public function view(User $user, WeeklyReport $report): bool
    {
        if ($user->isAdmin() || $user->id === $report->stagiaire_id) {
            return true;
        }

        return $report->stagiaire->stagesEnTantQueStagiaire()
            ->where('mentor_id', $user->id)
            ->exists();
    }
}
