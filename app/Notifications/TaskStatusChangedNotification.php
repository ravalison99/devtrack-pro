<?php

namespace App\Notifications;

use App\Models\Task;

class TaskStatusChangedNotification extends BaseNotification
{
    public function __construct(
        protected Task $task,
        protected string $ancienStatut,
        protected string $nouveauStatut
    ) {}

    protected function contenuMail(): array
    {
        return [
            'Statut de tâche modifié',
            "La tâche « {$this->task->titre} » est passée de « {$this->ancienStatut} » à « {$this->nouveauStatut} ».",
            'Voir la tâche',
            url("/tasks/{$this->task->id}"),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'titre' => $this->task->titre,
            'ancien_statut' => $this->ancienStatut,
            'nouveau_statut' => $this->nouveauStatut,
        ];
    }
}
