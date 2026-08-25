<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\User;
use App\Repositories\Contracts\JournalRepositoryInterface;

class JournalService
{
    public function __construct(protected JournalRepositoryInterface $entries) {}

    public function enregistrer(User $stagiaire, string $date, string $contenu): JournalEntry
    {
        $entreeExistante = $this->entries->findByStagiaireAndDate($stagiaire->id, $date);

        if ($entreeExistante !== null) {
            return $this->entries->update($entreeExistante, ['contenu' => $contenu]);
        }

        return $this->entries->create([
            'stagiaire_id' => $stagiaire->id,
            'date' => $date,
            'contenu' => $contenu,
        ]);
    }
}
