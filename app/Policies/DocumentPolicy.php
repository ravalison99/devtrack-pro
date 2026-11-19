<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Administrateur et mentor : tous les documents de l'application.
     * Stagiaire (chef de groupe ou non) : uniquement les documents des projets qui lui sont attribués.
     */
    public function view(User $user, Document $document): bool
    {
        if ($user->isAdmin() || $user->isMentor()) {
            return true;
        }

        return $document->project !== null && $user->can('view', $document->project);
    }
}
