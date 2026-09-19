<?php

namespace App\Repositories\Contracts;

use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TaskRepositoryInterface
{
    public function all(): Collection;
    public function findById(int $id): ?Task;
    public function findByProject(int $projectId): Collection;
    public function findForStagiaire(int $stagiaireId): Collection;
    public function findForMentor(int $mentorId): Collection;
    public function paginateAll(int $parPage = 5): LengthAwarePaginator;
    public function paginateForStagiaire(int $stagiaireId, int $parPage = 5): LengthAwarePaginator;
    public function paginateForMentor(int $mentorId, int $parPage = 5): LengthAwarePaginator;
    public function create(array $data): Task;
    public function updateStatut(Task $task, string $statut): Task;
    public function countByStatutForStagiaire(int $stagiaireId): array;
    public function count(): int;
}
