<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProjectRepositoryInterface
{
    public function all(): Collection;

    public function findById(int $id): ?Project;

    public function findWithDetails(int $id): ?Project;

    public function findForMentor(int $mentorId): Collection;

    public function findForChef(int $chefId): Collection;

    public function findNonAffectesForMentor(int $mentorId): Collection;

    public function paginateAll(int $parPage = 5): LengthAwarePaginator;

    public function paginateForStagiaire(int $stagiaireId, int $parPage = 5): LengthAwarePaginator;

    public function paginateForMentor(int $mentorId, int $parPage = 5): LengthAwarePaginator;

    public function create(array $data): Project;

    public function countForStagiaire(int $stagiaireId): int;

    public function count(): int;
}
