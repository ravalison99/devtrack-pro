<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProjectRepositoryInterface
{
    public function all(): Collection;
    public function findById(int $id): ?Project;
    public function findByStage(int $stageId): Collection;
    public function findForStagiaire(int $stagiaireId): Collection;
    public function findForMentor(int $mentorId): Collection;
    public function paginateAll(int $parPage = 5): LengthAwarePaginator;
    public function paginateForStagiaire(int $stagiaireId, int $parPage = 5): LengthAwarePaginator;
    public function paginateForMentor(int $mentorId, int $parPage = 5): LengthAwarePaginator;
    public function create(array $data): Project;
    public function update(Project $project, array $data): Project;
    public function count(): int;
}
