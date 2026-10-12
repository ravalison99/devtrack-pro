<?php

namespace App\Repositories\Eloquent;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Requêtes communes aux entités rattachées à un stage (projets, tâches).
 */
trait FiltreParMembreDuStage
{
    abstract protected function requete(): Builder;

    abstract protected function relationStage(): string;

    public function findForMentor(int $mentorId): Collection
    {
        return $this->parMembreDuStage('mentor_id', $mentorId)->get();
    }

    public function paginateAll(int $parPage = 5): LengthAwarePaginator
    {
        return $this->paginer($this->requete(), $parPage);
    }

    public function paginateForStagiaire(int $stagiaireId, int $parPage = 5): LengthAwarePaginator
    {
        return $this->paginer($this->parMembreDuStage('stagiaire_id', $stagiaireId), $parPage);
    }

    public function paginateForMentor(int $mentorId, int $parPage = 5): LengthAwarePaginator
    {
        return $this->paginer($this->parMembreDuStage('mentor_id', $mentorId), $parPage);
    }

    protected function parMembreDuStage(string $colonne, int $utilisateurId): Builder
    {
        return $this->requete()
            ->whereHas($this->relationStage(), fn (Builder $query) => $query->where($colonne, $utilisateurId));
    }

    protected function paginer(Builder $query, int $parPage): LengthAwarePaginator
    {
        return $query->orderByDesc('id')->paginate($parPage)->withQueryString();
    }
}
