<?php

namespace App\Policies;

use App\Models\User;

class StagePolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function updateStatus(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }
}
