<?php

namespace App\Notifications;

use App\Models\User;

class NewUserNotification extends BaseNotification
{
    public function __construct(protected User $utilisateur) {}

    protected function contenuMail(): array
    {
        return [
            'Nouvel utilisateur créé',
            "Un nouvel utilisateur « {$this->utilisateur->name} » ({$this->utilisateur->role}) a été créé.",
            'Voir les utilisateurs',
            url('/users'),
        ];
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
