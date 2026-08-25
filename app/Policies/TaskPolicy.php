<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function updateStatus(User $user, Task $task, string $nouveauStatut): bool
    {
        $mentorDuProjet = $task->project->stage->mentor_id;
        $stagiaireDuProjet = $task->project->stage->stagiaire_id;

        if ($user->isAdmin() || $user->id === $mentorDuProjet) {
            return true;
        }

        if ($user->id === $stagiaireDuProjet) {
            return $nouveauStatut !== 'termine';
        }

        return false;
    }
}
