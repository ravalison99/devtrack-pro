<?php

namespace App\Repositories\Eloquent;

use App\Models\JournalEntry;
use App\Repositories\Contracts\JournalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentJournalRepository implements JournalRepositoryInterface
{
    public function findByStagiaireAndDate(int $stagiaireId, string $date): ?JournalEntry
    {
        return JournalEntry::where('stagiaire_id', $stagiaireId)
            ->whereDate('date', $date)
            ->first();
    }

    public function findByStagiaire(int $stagiaireId): Collection
    {
        return JournalEntry::where('stagiaire_id', $stagiaireId)
            ->orderByDesc('date')
            ->get();
    }

    public function create(array $data): JournalEntry
    {
        return JournalEntry::create($data);
    }

    public function update(JournalEntry $entry, array $data): JournalEntry
    {
        $entry->update($data);
        return $entry;
    }
}
