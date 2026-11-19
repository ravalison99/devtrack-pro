<?php

namespace App\Listeners;

use App\Events\FichierRetire;
use App\Notifications\FileRemovedNotification;

class NotifyFichierRetire
{
    /** Le mentor du projet garde une trace du retrait d'un fichier. */
    public function handle(FichierRetire $event): void
    {
        $mentor = $event->project->mentor;

        if ($mentor === null || $mentor->id === $event->auteur->id) {
            return;
        }

        $mentor->notify(new FileRemovedNotification(
            $event->project,
            $event->titreDocument,
            $event->nomFichier,
            $event->auteur
        ));
    }
}
