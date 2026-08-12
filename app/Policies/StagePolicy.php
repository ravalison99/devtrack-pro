<?php

namespace App\Policies;

use App\Models\Stage;
use App\Models\User;

class StagePolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Stage $stage): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Stage $stage): bool
    {
        return $user->isAdmin()
            || $user->id === $stage->mentor_id
            || $user->id === $stage->stagiaire_id;
    }
}
