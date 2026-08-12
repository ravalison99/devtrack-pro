<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentProjectRepository implements ProjectRepositoryInterface
{
    public function all(): Collection
    {
        return Project::with('stage')->get();
    }

    public function findById(int $id): ?Project
    {
        return Project::with('stage')->find($id);
    }

    public function findByStage(int $stageId): Collection
    {
        return Project::where('stage_id', $stageId)->where('archive', false)->get();
    }

    public function create(array $data): Project
    {
        return Project::create($data);
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);
        return $project;
    }
}
