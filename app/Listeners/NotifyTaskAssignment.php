<?php

namespace App\Listeners;

use App\Events\TaskCreated;
use App\Notifications\TaskAssignedNotification;

class NotifyTaskAssignment
{
    public function handle(TaskCreated $event): void
    {
        $task = $event->task;

        // La tâche est confiée à son assigné ; sans assigné (projet historique), à tous les stagiaires du projet.
        $destinataires = $task->assigne
            ? collect([$task->assigne])
            : $task->project->stagiaires();

        foreach ($destinataires as $destinataire) {
            // Inutile de notifier quelqu'un de la tâche qu'il vient de s'assigner lui-même.
            if ($destinataire->id === auth()->id()) {
                continue;
            }

            $destinataire->notify(new TaskAssignedNotification($task));
        }
    }
}
