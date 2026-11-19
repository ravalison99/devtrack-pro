<?php

namespace App\Notifications;

use App\Models\Task;

class TaskOverdueNotification extends BaseNotification
{
    /** @param bool $mail vrai pour la personne à qui la tâche est confiée */
    public function __construct(protected Task $task, protected bool $mail = false) {}

    protected function message(): string
    {
        return "La tâche « {$this->task->titre} » est en retard (échéance du {$this->task->date_echeance->format('d/m/Y')}).";
    }

    protected function chemin(): string
    {
        return "/tasks/{$this->task->id}";
    }

    protected function sujet(): string
    {
        return 'Rappel : tâche en retard';
    }

    protected function libelleLien(): string
    {
        return 'Voir la tâche';
    }

    protected function icone(): string
    {
        return 'bi-exclamation-triangle';
    }

    protected function parMail(): bool
    {
        return $this->mail;
    }

    protected function donnees(): array
    {
        return ['task_id' => $this->task->id, 'titre' => $this->task->titre];
    }
}
