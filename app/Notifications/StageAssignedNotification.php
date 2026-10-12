<?php

namespace App\Notifications;

use App\Models\Stage;

class StageAssignedNotification extends BaseNotification
{
    public function __construct(protected Stage $stage) {}

    protected function contenuMail(): array
    {
        return [
            'Nouvelle affectation de stage',
            "Un stage a été créé associant {$this->stage->stagiaire->name} (stagiaire) et {$this->stage->mentor->name} (mentor).",
            'Voir les stages',
            url('/stages'),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'stage_id' => $this->stage->id,
            'stagiaire' => $this->stage->stagiaire->name,
            'mentor' => $this->stage->mentor->name,
        ];
    }
}
