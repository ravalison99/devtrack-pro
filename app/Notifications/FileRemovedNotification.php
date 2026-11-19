<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;

class FileRemovedNotification extends BaseNotification
{
    public function __construct(
        protected Project $project,
        protected string $titreDocument,
        protected string $nomFichier,
        protected User $auteur
    ) {}

    protected function message(): string
    {
        return "{$this->auteur->name} a retiré le fichier « {$this->nomFichier} » (document « {$this->titreDocument} ») du projet « {$this->project->nom} ».";
    }

    protected function chemin(): string
    {
        return "/projects/{$this->project->id}";
    }

    protected function icone(): string
    {
        return 'bi-trash';
    }

    protected function donnees(): array
    {
        return ['project_id' => $this->project->id, 'fichier' => $this->nomFichier];
    }
}
