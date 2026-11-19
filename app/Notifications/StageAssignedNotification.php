<?php

namespace App\Notifications;

use App\Models\Stage;

class StageAssignedNotification extends BaseNotification
{
    public function __construct(protected Stage $stage) {}

    protected function message(): string
    {
        $stagiaire = $this->stage->stagiaire?->name ?? 'Compte supprimé';
        $mentor = $this->stage->mentor?->name ?? 'Compte supprimé';

        return "Nouveau stage : {$stagiaire} (stagiaire) et {$mentor} (mentor).";
    }

    protected function chemin(): string
    {
        return '/stages';
    }

    protected function sujet(): string
    {
        return 'Nouvelle affectation de stage';
    }

    protected function libelleLien(): string
    {
        return 'Voir les stages';
    }

    protected function icone(): string
    {
        return 'bi-mortarboard';
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
            'mentor' => $this->stage->mentor?->name,
        ];
    }
}
