<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function update(User $currentUser, User $target): bool
    {
        return $currentUser->isAdmin() || $currentUser->id === $target->id;
    }

    public function updateRole(User $currentUser): bool
    {
        return $currentUser->isAdmin();
    }
}