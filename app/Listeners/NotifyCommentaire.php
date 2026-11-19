<?php

namespace App\Listeners;

use App\Events\CommentaireAjoute;
use App\Notifications\TaskCommentedNotification;

class NotifyCommentaire
{
    /** Un message sur une tâche prévient les autres participants, jamais son auteur. */
    public function handle(CommentaireAjoute $event): void
    {
        $comment = $event->comment;

        $comment->task->participants()
            ->reject(fn ($participant) => $participant->id === $comment->utilisateur_id)
            ->each(fn ($participant) => $participant->notify(new TaskCommentedNotification($comment)));
    }
}
