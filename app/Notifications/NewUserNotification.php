<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewUserNotification extends Notification
{
    use Queueable;

    public function __construct(protected User $utilisateur) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouvel utilisateur créé')
            ->line("Un nouvel utilisateur « {$this->utilisateur->name} » ({$this->utilisateur->role}) a été créé.")
            ->action('Voir les utilisateurs', url('/users'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $this->utilisateur->id,
            'name' => $this->utilisateur->name,
            'role' => $this->utilisateur->role,
        ];
    }
}
