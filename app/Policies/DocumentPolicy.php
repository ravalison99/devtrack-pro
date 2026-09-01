<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Document $document): bool
    {
        return $user->isAdmin() || $user->id === $document->utilisateur_id;
    }
}
