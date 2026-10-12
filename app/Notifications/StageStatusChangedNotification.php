<?php

namespace App\Notifications;

use App\Models\Stage;

class StageStatusChangedNotification extends BaseNotification
{
    public function __construct(
        protected Stage $stage,
        protected string $ancienStatut,
        protected string $nouveauStatut
    ) {}

    protected function contenuMail(): array
    {
        return [
            'Statut de stage modifié',
            "Le stage de {$this->stage->stagiaire->name} est passé de « {$this->ancienStatut} » à « {$this->nouveauStatut} ».",
            'Voir les stages',
            url('/stages'),
        ];
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
