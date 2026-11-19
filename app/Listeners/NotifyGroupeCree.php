<?php

namespace App\Listeners;

use App\Events\GroupeCree;
use App\Notifications\GroupeCreatedNotification;

class NotifyGroupeCree
{
    /** Chaque stagiaire apprend qu'il a été ajouté au groupe, et le chef sait qu'il l'est. */
    public function handle(GroupeCree $event): void
    {
        $groupe = $event->groupe;

        $groupe->membres->each(
            fn ($membre) => $membre->notify(new GroupeCreatedNotification($groupe, $membre->id === $groupe->chef_id))
        );
    }
}
