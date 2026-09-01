<?php

namespace App\Listeners;

use App\Events\TaskStatusChanged;
use App\Notifications\TaskStatusChangedNotification;

class LogTaskStatusChanged
{
    public function handle(TaskStatusChanged $event): void
    {
        $destinataires = collect([$event->task->project->stage->mentor, $event->task->project->stage->stagiaire])
            ->filter();

        foreach ($destinataires as $destinataire) {
            $destinataire->notify(new TaskStatusChangedNotification(
                $event->task,
                $event->ancienStatut,
                $event->nouveauStatut
            ));
        }
    }
}
