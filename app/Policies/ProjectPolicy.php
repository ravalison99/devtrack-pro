<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /** Seul un mentor peut créer un projet. */
    public function create(User $user): bool
    {
        return $user->isMentor();
    }

    public function update(User $user, Project $project): bool
    {
        return $user->isMentor() && $user->id === $project->mentorId();
    }

    /**
     * Création et répartition des tâches : mentor du projet, ou chef du groupe auquel le projet est confié.
     */
    public function gererTaches(User $user, Project $project): bool
    {
        return ($user->isMentor() && $user->id === $project->mentorId())
            || ($user->isStagiaire() && $project->estChef($user));
    }

    /**
     * Fichier du cahier des charges : administrateur et mentor (tous les documents de l'application),
     * ou stagiaire à qui le projet est attribué.
     */
    public function voirCahierDesCharges(User $user, Project $project): bool
    {
        return $user->isAdmin() || $user->isMentor() || $this->view($user, $project);
    }

    /** Consultation : administrateur, mentor du projet, ou stagiaire membre du groupe affecté. */
    public function view(User $user, Project $project): bool
    {
        return $user->isAdmin()
            || $user->id === $project->mentorId()
            || $project->aPourStagiaire($user);
    }

    /**
     * Dépôt de documents : réservé au chef du groupe affecté à ce projet.
     * Le chef est une désignation (groupes.chef_id), pas un rôle.
     */
    public function deposerDocument(User $user, Project $project): bool
    {
        return $user->isStagiaire()
            && $project->groupe !== null
            && $project->groupe->chef_id === $user->id;
    }
}
