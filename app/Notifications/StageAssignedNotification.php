<?php

namespace App\Notifications;

use App\Models\Stage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class StageAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(protected Stage $stage) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouvelle affectation de stage')
            ->line("Un stage a été créé associant {$this->stage->stagiaire->name} (stagiaire) et {$this->stage->mentor->name} (mentor).")
            ->action('Voir les stages', url('/stages'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'stage_id' => $this->stage->id,
            'stagiaire' => $this->stage->stagiaire->name,
            'mentor' => $this->stage->mentor->name,
        ];
    }
}
