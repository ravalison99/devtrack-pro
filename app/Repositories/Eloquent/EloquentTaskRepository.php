<?php

namespace App\Repositories\Eloquent;

use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EloquentTaskRepository implements TaskRepositoryInterface
{
    use FiltreParAccesAuProjet;

    public function findById(int $id): ?Task
    {
        return Task::with([
            'project',
            'assigne',
            'comments' => fn ($q) => $q->orderBy('id'),
            'comments.utilisateur',
            'attachments' => fn ($q) => $q->orderBy('id'),
            'attachments.utilisateur',
        ])->find($id);
    }

    public function findByProject(int $projectId): Collection
    {
        return Task::with('assigne')->where('project_id', $projectId)->get();
    }

    /** Tâches d'un projet visibles par un stagiaire : celles qui lui sont assignées (toutes pour le chef de groupe). */
    public function findByProjectVisiblesPar(int $projectId, int $stagiaireId): Collection
    {
        return Task::with('assigne')
            ->where('project_id', $projectId)
            ->visiblesPar($stagiaireId)
            ->get();
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
        return $this->pourStagiaire($stagiaireId)
            ->selectRaw('statut, count(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->toArray();
    }

    public function count(): int
    {
        return Task::count();
    }

    /** Pour un stagiaire, la visibilité dépend de l'assignation et non du simple accès au projet. */
    protected function pourStagiaire(int $stagiaireId): Builder
    {
        return $this->requete()->visiblesPar($stagiaireId);
    }

    protected function requete(): Builder
    {
        return Task::with(['project', 'assigne', 'comments', 'attachments']);
    }

    protected function relationProjet(): ?string
    {
        return 'project';
    }
}
