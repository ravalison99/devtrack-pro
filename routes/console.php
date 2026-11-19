<?php

use App\Console\Commands\PurgeNotifications;
use App\Console\Commands\RemindJournalEntry;
use App\Console\Commands\RemindOverdueTasks;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(RemindJournalEntry::class)->dailyAt('18:00');
Schedule::command(RemindOverdueTasks::class)->dailyAt('08:00');
Schedule::command(PurgeNotifications::class)->dailyAt('03:00');
