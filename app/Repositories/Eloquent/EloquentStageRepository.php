<?php

namespace App\Repositories\Eloquent;

use App\Models\Stage;
use App\Repositories\Contracts\StageRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EloquentStageRepository implements StageRepositoryInterface
{
    protected const STATUT_EN_COURS = 'en_cours';

    public function all(): Collection
    {
        return $this->requete()->get();
    }

    public function findById(int $id): ?Stage
    {
        return $this->requete()->find($id);
    }

    public function findByStagiaire(int $stagiaireId): ?Stage
    {
        return Stage::with(['mentor', 'projects'])
            ->where('stagiaire_id', $stagiaireId)
            ->whereIn('statut', ['planifie', self::STATUT_EN_COURS])
            ->first();
    }

    public function findByMentor(int $mentorId): Collection
    {
        return $this->requete()->where('mentor_id', $mentorId)->get();
    }

    public function findActifs(): Collection
    {
        return $this->requete()->where('statut', self::STATUT_EN_COURS)->get();
    }

    public function paginateAll(int $parPage = 5): LengthAwarePaginator
    {
        return $this->paginer($this->requete(), $parPage);
    }

    public function paginateForMentor(int $mentorId, array $filtres = [], int $parPage = 5): LengthAwarePaginator
    {
        $query = $this->requete()->where('mentor_id', $mentorId);

        if (! empty($filtres['statut'])) {
            $query->where('statut', $filtres['statut']);
        }

        if (! empty($filtres['stagiaire_id'])) {
            $query->where('stagiaire_id', (int) $filtres['stagiaire_id']);
        }

        if (! empty($filtres['date_debut'])) {
            $query->whereDate('date_debut', '>=', $filtres['date_debut']);
        }

        if (! empty($filtres['date_fin'])) {
            $query->whereDate('date_fin', '<=', $filtres['date_fin']);
        }

        return $this->paginer($query, $parPage);
    }

    public function paginateForStagiaire(int $stagiaireId, int $parPage = 5): LengthAwarePaginator
    {
        return $this->paginer($this->requete()->where('stagiaire_id', $stagiaireId), $parPage);
    }

    public function create(array $data): Stage
    {
        return Stage::create($data);
    }

    public function update(Stage $stage, array $data): Stage
    {
        $stage->update($data);
        return $stage;
    }

    public function delete(Stage $stage): bool
    {
        return $stage->delete();
    }

    public function countActifs(): int
    {
        return $this->countByStatut(self::STATUT_EN_COURS);
    }

    public function countByStatut(string $statut): int
    {
        return Stage::where('statut', $statut)->count();
    }

    protected function requete(): Builder
    {
        return Stage::with(['stagiaire', 'mentor']);
    }

    protected function paginer(Builder $query, int $parPage): LengthAwarePaginator
    {
        return $query->orderByDesc('date_debut')->paginate($parPage)->withQueryString();
    }
}
