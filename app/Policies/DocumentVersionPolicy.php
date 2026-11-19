<?php

namespace App\Policies;

use App\Models\DocumentVersion;
use App\Models\User;

class DocumentVersionPolicy
{
    /**
     * Retirer un fichier déposé sur un projet : réservé au chef de groupe qui l'a lui-même déposé.
     * Ni le mentor, ni l'administrateur, ni les autres membres n'ont ce droit.
     */
    public function delete(User $user, DocumentVersion $version): bool
    {
        $document = $version->document;
        $projet = $document->project;

        if ($projet === null) {
            return false;
        }

        $deposant = $version->utilisateur_id ?? $document->utilisateur_id;

        return $user->id === $deposant && $projet->estChef($user);
    }
}
