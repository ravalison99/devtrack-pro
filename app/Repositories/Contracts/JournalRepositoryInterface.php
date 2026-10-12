<?php

namespace App\Repositories\Contracts;

use App\Models\JournalEntry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface JournalRepositoryInterface
{
    public function findByStagiaireAndDate(int $stagiaireId, string $date): ?JournalEntry;
    public function paginateByStagiaire(int $stagiaireId, int $parPage = 5): LengthAwarePaginator;
    public function create(array $data): JournalEntry;
    public function update(JournalEntry $entry, array $data): JournalEntry;
}
