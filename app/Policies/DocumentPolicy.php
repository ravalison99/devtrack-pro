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
        if ($user->isAdmin() || $user->id === $document->utilisateur_id) {
            return true;
        }

        if ($document->project_id !== null) {
            $stage = $document->project->stage;

            return $user->id === $stage->mentor_id || $user->id === $stage->stagiaire_id;
        }

        return false;
    }
}
