<?php

namespace App\Listeners;

use App\Events\StageStatusChanged;
use App\Notifications\StageStatusChangedNotification;

class NotifyStageStatusChanged
{
    public function handle(StageStatusChanged $event): void
    {
        $destinataires = collect([$event->stage->mentor, $event->stage->stagiaire])->filter();

        foreach ($destinataires as $destinataire) {
            $destinataire->notify(new StageStatusChangedNotification(
                $event->stage,
                $event->ancienStatut,
                $event->nouveauStatut
            ));
        }
    }
}
