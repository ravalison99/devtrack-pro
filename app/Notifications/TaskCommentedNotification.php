<?php

namespace App\Notifications;

use App\Models\Comment;

class TaskCommentedNotification extends BaseNotification
{
    public function __construct(protected Comment $comment) {}

    protected function message(): string
    {
        $auteur = $this->comment->utilisateur?->name ?? "Quelqu'un";

        return "{$auteur} a commenté la tâche « {$this->comment->task->titre} ».";
    }

    protected function chemin(): string
    {
        return "/tasks/{$this->comment->task_id}";
    }

    protected function icone(): string
    {
        return 'bi-chat-left-text';
    }

    protected function donnees(): array
    {
        return ['task_id' => $this->comment->task_id, 'comment_id' => $this->comment->id];
    }
}
