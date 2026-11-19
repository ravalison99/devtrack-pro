<?php

namespace App\Repositories\Contracts;

use App\Models\JournalEntry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface JournalRepositoryInterface
{
    public function findByStagiaireAndDate(int $stagiaireId, string $date): ?JournalEntry;

    public function paginateByStagiaire(int $stagiaireId, int $parPage = 5): LengthAwarePaginator;

    /** @return array<int, string> dates (Y-m-d) pour lesquelles le stagiaire a écrit une entrée, depuis la date donnée */
    public function datesRemplies(int $stagiaireId, string $depuis): array;

    public function create(array $data): JournalEntry;

    public function update(JournalEntry $entry, array $data): JournalEntry;
}
