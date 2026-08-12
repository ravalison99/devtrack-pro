<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;

interface ProjectRepositoryInterface
{
    public function all(): Collection;
    public function findById(int $id): ?Project;
    public function findByStage(int $stageId): Collection;
    public function create(array $data): Project;
    public function update(Project $project, array $data): Project;
}
