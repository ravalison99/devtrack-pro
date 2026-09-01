<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TaskStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Task $task,
        protected string $ancienStatut,
        protected string $nouveauStatut
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Statut de tâche modifié')
            ->line("La tâche « {$this->task->titre} » est passée de « {$this->ancienStatut} » à « {$this->nouveauStatut} ».")
            ->action('Voir la tâche', url("/tasks/{$this->task->id}"));
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
