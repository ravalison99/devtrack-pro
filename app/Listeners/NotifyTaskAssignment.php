<?php

namespace App\Listeners;

use App\Events\TaskCreated;
use App\Notifications\TaskAssignedNotification;

class NotifyTaskAssignment
{
    public function handle(TaskCreated $event): void
    {
        $stagiaire = $event->task->project->stage->stagiaire;

        $stagiaire?->notify(new TaskAssignedNotification($event->task));
    }
}
