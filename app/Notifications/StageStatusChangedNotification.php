<?php

namespace App\Notifications;

use App\Models\Stage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class StageStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Stage $stage,
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
            ->subject('Statut de stage modifié')
            ->line("Le stage de {$this->stage->stagiaire->name} est passé de « {$this->ancienStatut} » à « {$this->nouveauStatut} ».")
            ->action('Voir les stages', url('/stages'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'stage_id' => $this->stage->id,
            'stagiaire' => $this->stage->stagiaire->name,
            'ancien_statut' => $this->ancienStatut,
            'nouveau_statut' => $this->nouveauStatut,
        ];
    }
}
