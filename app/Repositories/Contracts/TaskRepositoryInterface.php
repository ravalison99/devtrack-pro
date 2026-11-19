<?php

namespace App\Repositories\Contracts;

use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TaskRepositoryInterface
{
    public function findById(int $id): ?Task;

    public function findByProject(int $projectId): Collection;

    public function findByProjectVisiblesPar(int $projectId, int $stagiaireId): Collection;

    /** Tâches visibles par l'utilisateur selon son rôle, avec filtres (statut, retard, recherche). */
    public function paginateFor(User $user, array $filtres = [], int $parPage = 10): LengthAwarePaginator;

    /** Mes tâches non terminées, les plus urgentes d'abord. */
    public function aFaireMaintenant(int $stagiaireId, int $limite = 6): Collection;

    /** @return array{total: int, terminees: int, en_retard: int, pourcentage: int} */
    public function progressionDe(int $stagiaireId): array;

    /** @return array{total: int, terminees: int, en_retard: int, pourcentage: int} */
    public function progressionProjet(int $projectId): array;

    public function findForMentor(int $mentorId): Collection;

    public function paginateAll(int $parPage = 5): LengthAwarePaginator;

    public function paginateForStagiaire(int $stagiaireId, int $parPage = 5): LengthAwarePaginator;

    public function paginateForMentor(int $mentorId, int $parPage = 5): LengthAwarePaginator;

    public function create(array $data): Task;

    public function updateStatut(Task $task, string $statut): Task;

    public function countByStatutForStagiaire(int $stagiaireId): array;

    public function count(): int;
}
