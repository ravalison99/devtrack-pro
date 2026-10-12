<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EloquentProjectRepository implements ProjectRepositoryInterface
{
    use FiltreParMembreDuStage;

    public function all(): Collection
    {
        return Project::with('stage')->get();
    }

    public function findById(int $id): ?Project
    {
        return Project::with('stage')->find($id);
    }

    public function create(array $data): Project
    {
        return Project::create($data);
    }

    public function count(): int
    {
        return Project::count();
    }

    protected function requete(): Builder
    {
        return Project::with('stage');
    }

    protected function relationStage(): string
    {
        return 'stage';
    }
}
