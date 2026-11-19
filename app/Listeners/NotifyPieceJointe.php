<?php

namespace App\Listeners;

use App\Events\PieceJointeAjoutee;
use App\Notifications\TaskAttachmentAddedNotification;

class NotifyPieceJointe
{
    /** Un fichier rendu sur une tâche prévient l'assigné, le chef de groupe et le mentor, hors auteur. */
    public function handle(PieceJointeAjoutee $event): void
    {
        $piece = $event->attachment;

        $piece->task->participants()
            ->reject(fn ($participant) => $participant->id === $piece->utilisateur_id)
            ->each(fn ($participant) => $participant->notify(new TaskAttachmentAddedNotification($piece)));
    }
}
