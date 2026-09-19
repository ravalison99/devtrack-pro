<?php

namespace App\Repositories\Contracts;

use App\Models\Stage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface StageRepositoryInterface
{
    public function all(): Collection;
    public function findById(int $id): ?Stage;
    public function findByStagiaire(int $stagiaireId): ?Stage;
    public function findByMentor(int $mentorId): Collection;
    public function allForStagiaire(int $stagiaireId): Collection;
    public function findActifs(): Collection;
    public function paginateAll(int $parPage = 5): LengthAwarePaginator;
    public function paginateForMentor(int $mentorId, array $filtres = [], int $parPage = 5): LengthAwarePaginator;
    public function paginateForStagiaire(int $stagiaireId, int $parPage = 5): LengthAwarePaginator;
    public function create(array $data): Stage;
    public function update(Stage $stage, array $data): Stage;
    public function delete(Stage $stage): bool;
    public function countActifs(): int;
    public function countByStatut(string $statut): int;
}
