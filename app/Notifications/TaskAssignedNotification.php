<?php

namespace App\Notifications;

use App\Models\Task;

class TaskAssignedNotification extends BaseNotification
{
    public function __construct(protected Task $task) {}

    protected function message(): string
    {
        return "Nouvelle tâche « {$this->task->titre} » assignée (projet « {$this->task->project->nom} »).";
    }

    protected function chemin(): string
    {
        return "/tasks/{$this->task->id}";
    }

    protected function sujet(): string
    {
        return 'Nouvelle tâche assignée';
    }

    protected function libelleLien(): string
    {
        return 'Voir la tâche';
    }

    protected function icone(): string
    {
        return 'bi-list-check';
    }

    protected function parMail(): bool
    {
        return true;
    }

    protected function donnees(): array
    {
        return [
            'task_id' => $this->task->id,
            'titre' => $this->task->titre,
            'projet' => $this->task->project->nom,
        ];
    }
}
