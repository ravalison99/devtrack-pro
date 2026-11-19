<?php

namespace App\Notifications;

use App\Models\Groupe;

class GroupeCreatedNotification extends BaseNotification
{
    public function __construct(protected Groupe $groupe, protected bool $estChef) {}

    protected function message(): string
    {
        $base = "Vous avez été ajouté au groupe « {$this->groupe->nom} » pour le projet « {$this->groupe->project->nom} ».";

        return $this->estChef
            ? $base.' Vous en êtes le chef : vous répartissez les tâches et déposez les documents.'
            : $base.' Chef de groupe : '.($this->groupe->chef?->name ?? 'non défini').'.';
    }

    protected function chemin(): string
    {
        return "/projects/{$this->groupe->project_id}";
    }

    protected function sujet(): string
    {
        return 'Vous avez été ajouté à un groupe de projet';
    }

    protected function libelleLien(): string
    {
        return 'Voir le projet';
    }

    protected function icone(): string
    {
        return 'bi-people';
    }

    protected function parMail(): bool
    {
        return true;
    }

    protected function donnees(): array
    {
        return ['groupe_id' => $this->groupe->id, 'project_id' => $this->groupe->project_id, 'chef' => $this->estChef];
    }
}
