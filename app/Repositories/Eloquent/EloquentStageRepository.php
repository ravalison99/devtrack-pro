<?php

namespace App\Repositories\Eloquent;

use App\Models\Stage;
use App\Repositories\Contracts\StageRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentStageRepository implements StageRepositoryInterface
{
    public function all(): Collection
    {
        return Stage::with(['stagiaire', 'mentor'])->get();
    }

    public function findById(int $id): ?Stage
    {
        return Stage::with(['stagiaire', 'mentor'])->find($id);
    }

    public function findByStagiaire(int $stagiaireId): ?Stage
    {
        return Stage::with(['mentor', 'projects'])
            ->where('stagiaire_id', $stagiaireId)
            ->whereIn('statut', ['planifie', 'en_cours'])
            ->first();
    }

    public function findByMentor(int $mentorId): Collection
    {
        return Stage::with(['stagiaire', 'mentor'])->where('mentor_id', $mentorId)->get();
    }

    public function allForStagiaire(int $stagiaireId): Collection
    {
        return Stage::with(['stagiaire', 'mentor'])
            ->where('stagiaire_id', $stagiaireId)
            ->orderByDesc('date_debut')
            ->get();
    }

    public function findActifs(): Collection
    {
        return Stage::with(['stagiaire', 'mentor'])->where('statut', 'en_cours')->get();
    }

    public function paginateAll(int $parPage = 5): LengthAwarePaginator
    {
        return Stage::with(['stagiaire', 'mentor'])
            ->orderByDesc('date_debut')
            ->paginate($parPage)
            ->withQueryString();
    }

    public function paginateForMentor(int $mentorId, array $filtres = [], int $parPage = 5): LengthAwarePaginator
    {
        $query = Stage::with(['stagiaire', 'mentor'])->where('mentor_id', $mentorId);

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

        return $query->orderByDesc('date_debut')->paginate($parPage)->withQueryString();
    }

    public function paginateForStagiaire(int $stagiaireId, int $parPage = 5): LengthAwarePaginator
    {
        return Stage::with(['stagiaire', 'mentor'])
            ->where('stagiaire_id', $stagiaireId)
            ->orderByDesc('date_debut')
            ->paginate($parPage)
            ->withQueryString();
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
        return $this->countByStatut('en_cours');
    }

    public function countByStatut(string $statut): int
    {
        return Stage::where('statut', $statut)->count();
    }
}
