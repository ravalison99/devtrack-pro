<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TaskAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(protected Task $task) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouvelle tâche assignée')
            ->line("Une nouvelle tâche « {$this->task->titre} » vous a été assignée dans le projet « {$this->task->project->nom} ».")
            ->action('Voir la tâche', url("/tasks/{$this->task->id}"));
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
