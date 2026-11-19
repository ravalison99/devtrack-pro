<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Administrateur et mentor du projet : toutes les tâches.
     * Stagiaire : seulement les tâches qui lui sont assignées (toutes celles du projet s'il est chef de groupe).
     */
    public function view(User $user, Task $task): bool
    {
        return $user->isAdmin()
            || $user->id === $task->project->mentorId()
            || $task->estVisiblePar($user);
    }

    /**
     * Fichiers joints à une tâche : administrateur et mentor (tous les documents de l'application),
     * sinon uniquement les personnes qui peuvent voir la tâche.
     */
    public function voirPiecesJointes(User $user, Task $task): bool
    {
        return $user->isAdmin() || $user->isMentor() || $this->view($user, $task);
    }

    public function updateStatus(User $user, Task $task, string $nouveauStatut): bool
    {
        if ($user->isAdmin() || $user->id === $task->project->mentorId()) {
            return true;
        }

        // Un stagiaire (assigné ou chef de groupe) ne peut pas valider une tâche comme terminée.
        if ($task->estVisiblePar($user)) {
            return $nouveauStatut !== 'termine';
        }

        return false;
    }
}
