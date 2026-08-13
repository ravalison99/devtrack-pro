<?php

namespace App\Repositories\Contracts;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

interface TaskRepositoryInterface
{
    public function all(): Collection;
    public function findById(int $id): ?Task;
    public function findByProject(int $projectId): Collection;
    public function create(array $data): Task;
    public function updateStatut(Task $task, string $statut): Task;
}
