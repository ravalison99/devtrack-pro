<?php

namespace App\Policies;

use App\Models\User;

class GroupePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isMentor();
    }

    public function create(User $user): bool
    {
        return $user->isMentor();
    }
}
