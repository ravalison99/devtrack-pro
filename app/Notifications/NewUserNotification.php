<?php

namespace App\Notifications;

use App\Models\User;

class NewUserNotification extends BaseNotification
{
    public function __construct(protected User $utilisateur) {}

    protected function message(): string
    {
        return "Nouvel utilisateur « {$this->utilisateur->name} » ({$this->utilisateur->role}).";
    }

    protected function chemin(): string
    {
        return '/users';
    }

    protected function sujet(): string
    {
        return 'Nouvel utilisateur créé';
    }

    protected function libelleLien(): string
    {
        return 'Voir les utilisateurs';
    }

    protected function icone(): string
    {
        return 'bi-person-plus';
    }

    protected function parMail(): bool
    {
        return true;
    }

    protected function donnees(): array
    {
        return [
            'user_id' => $this->utilisateur->id,
            'name' => $this->utilisateur->name,
            'role' => $this->utilisateur->role,
        ];
    }
}
