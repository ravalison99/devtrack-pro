<?php

namespace App\Listeners;

use App\Events\TaskStatusChanged;
use App\Notifications\TaskStatusChangedNotification;

class LogTaskStatusChanged
{
    /** Le mentor, l'assigné et le chef de groupe sont prévenus, sauf la personne qui vient de changer le statut. */
    public function handle(TaskStatusChanged $event): void
    {
        $task = $event->task;

        $task->participants()
            ->reject(fn ($participant) => $participant->id === auth()->id())
            ->each(fn ($participant) => $participant->notify(new TaskStatusChangedNotification(
                $task,
                $event->ancienStatut,
                $event->nouveauStatut
            )));
    }
}
