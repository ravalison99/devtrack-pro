<?php

namespace App\Repositories\Eloquent;

use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EloquentTaskRepository implements TaskRepositoryInterface
{
    use FiltreParAccesAuProjet;

    protected const STATUTS = ['a_faire', 'en_cours', 'en_revue', 'termine'];

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

    public function paginateFor(User $user, array $filtres = [], int $parPage = 10): LengthAwarePaginator
    {
        $query = match (true) {
            $user->isAdmin() => $this->requete(),
            $user->isMentor() => $this->pourMentor($user->id),
            default => $this->pourStagiaire($user->id),
        };

        if (in_array($filtres['statut'] ?? null, self::STATUTS, true)) {
            $query->where('statut', $filtres['statut']);
        }

        if (! empty($filtres['retard'])) {
            $query->whereNotNull('date_echeance')
                ->whereDate('date_echeance', '<', today()->toDateString())
                ->where('statut', '!=', 'termine');
        }

        if (! empty($filtres['q'])) {
            $query->where('titre', 'like', '%'.addcslashes(trim($filtres['q']), '%_\\').'%');
        }

        return $this->paginer($query, $parPage);
    }

    public function aFaireMaintenant(int $stagiaireId, int $limite = 6): Collection
    {
        return Task::with(['project', 'assigne'])
            ->de($stagiaireId)
            ->where('statut', '!=', 'termine')
            ->orderByRaw('CASE WHEN date_echeance IS NULL THEN 1 ELSE 0 END')
            ->orderBy('date_echeance')
            ->orderByRaw("CASE priorite WHEN 'haute' THEN 0 WHEN 'moyenne' THEN 1 ELSE 2 END")
            ->limit($limite)
            ->get();
    }

    public function progressionDe(int $stagiaireId): array
    {
        return $this->progression(Task::query()->de($stagiaireId));
    }

    public function progressionProjet(int $projectId): array
    {
        return $this->progression(Task::query()->where('project_id', $projectId));
    }

    /** @return array{total: int, terminees: int, en_retard: int, pourcentage: int} */
    protected function progression(Builder $query): array
    {
        $total = (clone $query)->count();
        $terminees = (clone $query)->where('statut', 'termine')->count();
        $enRetard = (clone $query)
            ->whereNotNull('date_echeance')
            ->whereDate('date_echeance', '<', today()->toDateString())
            ->where('statut', '!=', 'termine')
            ->count();

        return [
            'total' => $total,
            'terminees' => $terminees,
            'en_retard' => $enRetard,
            'pourcentage' => $total > 0 ? (int) round($terminees / $total * 100) : 0,
        ];
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
