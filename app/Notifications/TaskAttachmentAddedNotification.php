<?php

namespace App\Notifications;

use App\Models\Attachment;

class TaskAttachmentAddedNotification extends BaseNotification
{
    public function __construct(protected Attachment $attachment) {}

    protected function message(): string
    {
        $auteur = $this->attachment->utilisateur?->name ?? "Quelqu'un";

        return "{$auteur} a déposé le fichier « {$this->attachment->nom_fichier} » sur la tâche « {$this->attachment->task->titre} ».";
    }

    protected function chemin(): string
    {
        return "/tasks/{$this->attachment->task_id}";
    }

    protected function icone(): string
    {
        return 'bi-paperclip';
    }

    protected function donnees(): array
    {
        return ['task_id' => $this->attachment->task_id, 'attachment_id' => $this->attachment->id];
    }
}
