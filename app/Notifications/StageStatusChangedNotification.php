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

    protected function message(): string
    {
        $stagiaire = $this->stage->stagiaire?->name ?? 'Compte supprimé';

        return "Le stage de {$stagiaire} est passé de « {$this->ancienStatut} » à « {$this->nouveauStatut} ».";
    }

    protected function chemin(): string
    {
        return '/stages';
    }

    protected function sujet(): string
    {
        return 'Statut de stage modifié';
    }

    protected function libelleLien(): string
    {
        return 'Voir les stages';
    }

    protected function icone(): string
    {
        return 'bi-arrow-repeat';
    }

    protected function parMail(): bool
    {
        return true;
    }

    protected function donnees(): array
    {
        return [
            'stage_id' => $this->stage->id,
            'stagiaire' => $this->stage->stagiaire?->name,
            'ancien_statut' => $this->ancienStatut,
            'nouveau_statut' => $this->nouveauStatut,
        ];
    }
}
