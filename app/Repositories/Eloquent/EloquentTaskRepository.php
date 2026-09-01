<?php

namespace App\Repositories\Eloquent;

use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentTaskRepository implements TaskRepositoryInterface
{
    public function all(): Collection
    {
        return Task::with(['project', 'comments', 'attachments'])->get();
    }

    public function findById(int $id): ?Task
    {
        return Task::with(['project', 'comments.utilisateur', 'attachments'])->find($id);
    }

    public function findByProject(int $projectId): Collection
    {
        return Task::where('project_id', $projectId)->get();
    }

    public function create(array $data): Task
    {
        return Task::create($data);
    }

    public function updateStatut(Task $task, string $statut): Task
    {
        $task->update(['statut' => $statut]);
        return $task;
    }
    
    public function countByStatutForStagiaire(int $stagiaireId): array
    {
        return Task::whereHas('project.stage', function ($query) use ($stagiaireId) {
            $query->where('stagiaire_id', $stagiaireId);
        })
            ->selectRaw('statut, count(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->toArray();
    }
}
