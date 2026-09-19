<?php

namespace App\Listeners;

use App\Events\StageCreated;
use App\Notifications\StageAssignedNotification;

class NotifyStageAssignment
{
    public function handle(StageCreated $event): void
    {
        $destinataires = collect([$event->stage->mentor, $event->stage->stagiaire])->filter();

        foreach ($destinataires as $destinataire) {
            $destinataire->notify(new StageAssignedNotification($event->stage));
        }
    }
}
