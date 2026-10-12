<?php

namespace App\Notifications;

use App\Models\Task;

class TaskAssignedNotification extends BaseNotification
{
    public function __construct(protected Task $task) {}

    protected function contenuMail(): array
    {
        return [
            'Nouvelle tâche assignée',
            "Une nouvelle tâche « {$this->task->titre} » vous a été assignée dans le projet « {$this->task->project->nom} ».",
            'Voir la tâche',
            url("/tasks/{$this->task->id}"),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'titre' => $this->task->titre,
            'projet' => $this->task->project->nom,
        ];
    }
}
