<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RemindJournalEntry extends Command
{
    protected $signature = 'journal:remind';
    protected $description = 'Rappelle aux stagiaires sans entrée du jour de remplir leur journal';

    public function handle(): void
    {
        $aujourdHui = now()->toDateString();

        $stagiaires = User::where('role', 'stagiaire')->where('is_active', true)->get();

        foreach ($stagiaires as $stagiaire) {
            $aDejaEcrit = $stagiaire->journalEntries()->whereDate('date', $aujourdHui)->exists();

            if (! $aDejaEcrit) {
                Log::info("Rappel : {$stagiaire->name} n'a pas encore rempli son journal du {$aujourdHui}.");
            }
        }

        $this->info('Rappels de journal envoyés.');
    }
}
