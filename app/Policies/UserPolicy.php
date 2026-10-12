<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $currentUser, User $target): bool
    {
        return $currentUser->isAdmin() || $currentUser->id === $target->id;
    }

    public function delete(User $currentUser): bool
    {
        return $currentUser->isAdmin();
    }
}