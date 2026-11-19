<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\DocumentVersion;

class DocumentDeposedNotification extends BaseNotification
{
    /** @param bool $mail vrai pour le mentor du projet, qui assure le suivi ; faux pour les membres du groupe */
    public function __construct(
        protected Document $document,
        protected DocumentVersion $version,
        protected bool $mail = false
    ) {}

    protected function message(): string
    {
        $auteur = $this->version->utilisateur?->name ?? 'Le chef de groupe';

        return "{$auteur} a déposé « {$this->document->titre} » (version {$this->version->numero_version}) sur le projet « {$this->document->project->nom} ».";
    }

    protected function chemin(): string
    {
        return "/projects/{$this->document->project_id}";
    }

    protected function sujet(): string
    {
        return 'Nouveau document déposé sur un projet';
    }

    protected function libelleLien(): string
    {
        return 'Voir le projet';
    }

    protected function icone(): string
    {
        return 'bi-file-earmark-arrow-up';
    }

    protected function parMail(): bool
    {
        return $this->mail;
    }

    protected function donnees(): array
    {
        return [
            'project_id' => $this->document->project_id,
            'document_id' => $this->document->id,
            'version' => $this->version->numero_version,
        ];
    }
}
