<?php

namespace App\Listeners;

use App\Events\WeeklyReportSubmitted;
use Illuminate\Support\Facades\Log;

class LogWeeklyReportSubmitted
{
    public function handle(WeeklyReportSubmitted $event): void
    {
        Log::info("Rapport semaine {$event->report->semaine} soumis par {$event->report->stagiaire->name}.");
    }
}
