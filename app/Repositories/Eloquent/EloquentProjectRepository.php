<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    public function findForStagiaire(int $stagiaireId): Collection
    {
        return Project::with('stage')
            ->whereHas('stage', function ($query) use ($stagiaireId) {
                $query->where('stagiaire_id', $stagiaireId);
            })
            ->get();
    }

    public function findForMentor(int $mentorId): Collection
    {
        return Project::with('stage')
            ->whereHas('stage', function ($query) use ($mentorId) {
                $query->where('mentor_id', $mentorId);
            })
            ->get();
    }

    public function paginateAll(int $parPage = 5): LengthAwarePaginator
    {
        return Project::with('stage')->orderByDesc('id')->paginate($parPage)->withQueryString();
    }

    public function paginateForStagiaire(int $stagiaireId, int $parPage = 5): LengthAwarePaginator
    {
        return Project::with('stage')
            ->whereHas('stage', function ($query) use ($stagiaireId) {
                $query->where('stagiaire_id', $stagiaireId);
            })
            ->orderByDesc('id')
            ->paginate($parPage)
            ->withQueryString();
    }

    public function paginateForMentor(int $mentorId, int $parPage = 5): LengthAwarePaginator
    {
        return Project::with('stage')
            ->whereHas('stage', function ($query) use ($mentorId) {
                $query->where('mentor_id', $mentorId);
            })
            ->orderByDesc('id')
            ->paginate($parPage)
            ->withQueryString();
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

    public function count(): int
    {
        return Project::count();
    }
}
