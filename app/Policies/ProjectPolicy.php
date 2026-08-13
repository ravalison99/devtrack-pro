<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Stage;
use App\Models\User;

class ProjectPolicy
{
    public function create(User $user, Stage $stage): bool
    {
        return $user->isMentor() && $user->id === $stage->mentor_id;
    }

    public function update(User $user, Project $project): bool
    {
        return $user->isMentor() && $user->id === $project->stage->mentor_id;
    }

    public function view(User $user, Project $project): bool
    {
        $stage = $project->stage;

        return $user->isAdmin()
            || $user->id === $stage->mentor_id
            || $user->id === $stage->stagiaire_id;
    }
}
