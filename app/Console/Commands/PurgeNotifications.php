<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

class PurgeNotifications extends Command
{
    protected $signature = 'notifications:purger {--jours=30 : Âge (en jours) à partir duquel une notification lue est supprimée} {--jours-non-lues=180 : Âge (en jours) à partir duquel même une notification non lue est supprimée}';

    protected $description = 'Supprime les anciennes notifications pour garder la base légère';

    public function handle(): int
    {
        $lues = DatabaseNotification::whereNotNull('read_at')
            ->where('read_at', '<', now()->subDays((int) $this->option('jours')))
            ->delete();

        $nonLues = DatabaseNotification::whereNull('read_at')
            ->where('created_at', '<', now()->subDays((int) $this->option('jours-non-lues')))
            ->delete();

        $this->info("Notifications supprimées : {$lues} lue(s), {$nonLues} non lue(s) très ancienne(s).");

        return self::SUCCESS;
    }
}
