<?php

namespace App\Listeners;

use App\Events\DocumentDepose;
use App\Notifications\DocumentDeposedNotification;

class NotifyDocumentDepose
{
    /**
     * Le mentor du projet assure le suivi : notification et mail.
     * Les autres membres du groupe sont prévenus dans l'application seulement.
     */
    public function handle(DocumentDepose $event): void
    {
        $projet = $event->document->project;
        $deposant = $event->version->utilisateur_id;

        if ($projet === null) {
            return;
        }

        if ($projet->mentor && $projet->mentor->id !== $deposant) {
            $projet->mentor->notify(new DocumentDeposedNotification($event->document, $event->version, true));
        }

        ($projet->groupe?->membres ?? collect())
            ->reject(fn ($membre) => $membre->id === $deposant)
            ->each(fn ($membre) => $membre->notify(new DocumentDeposedNotification($event->document, $event->version, false)));
    }
}
