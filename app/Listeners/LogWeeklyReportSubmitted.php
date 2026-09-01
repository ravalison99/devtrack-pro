<?php

namespace App\Listeners;

use App\Events\WeeklyReportSubmitted;
use App\Notifications\WeeklyReportSubmittedNotification;

class LogWeeklyReportSubmitted
{
    public function handle(WeeklyReportSubmitted $event): void
    {
        $mentor = $event->report->stagiaire->stagesEnTantQueStagiaire()->first()?->mentor;

        $mentor?->notify(new WeeklyReportSubmittedNotification($event->report));
    }
}
