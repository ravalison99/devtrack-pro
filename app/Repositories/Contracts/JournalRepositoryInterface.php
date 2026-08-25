<?php

namespace App\Repositories\Contracts;

use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Collection;

interface JournalRepositoryInterface
{
    public function findByStagiaireAndDate(int $stagiaireId, string $date): ?JournalEntry;
    public function findByStagiaire(int $stagiaireId): Collection;
    public function create(array $data): JournalEntry;
    public function update(JournalEntry $entry, array $data): JournalEntry;
}
