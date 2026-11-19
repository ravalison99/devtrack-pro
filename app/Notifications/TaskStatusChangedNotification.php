<?php

namespace App\Notifications;

use App\Models\Task;

class TaskStatusChangedNotification extends BaseNotification
{
    /** Statuts qui méritent un mail : la tâche est à relire ou validée. Les autres passages restent dans l'application. */
    public const STATUTS_PAR_MAIL = ['en_revue', 'termine'];

    public function __construct(
        protected Task $task,
        protected string $ancienStatut,
        protected string $nouveauStatut
    ) {}

    protected function message(): string
    {
        return "Tâche « {$this->task->titre} » : « {$this->ancienStatut} » → « {$this->nouveauStatut} ».";
    }

    protected function chemin(): string
    {
        return "/tasks/{$this->task->id}";
    }

    protected function sujet(): string
    {
        return 'Statut de tâche modifié';
    }

    protected function libelleLien(): string
    {
        return 'Voir la tâche';
    }

    protected function icone(): string
    {
        return 'bi-arrow-repeat';
    }

    protected function parMail(): bool
    {
        return in_array($this->nouveauStatut, self::STATUTS_PAR_MAIL, true);
    }

    protected function donnees(): array
    {
        return [
            'task_id' => $this->task->id,
            'titre' => $this->task->titre,
            'ancien_statut' => $this->ancienStatut,
            'nouveau_statut' => $this->nouveauStatut,
        ];
    }
}
