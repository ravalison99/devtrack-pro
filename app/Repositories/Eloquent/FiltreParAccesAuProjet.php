<?php

namespace App\Repositories\Eloquent;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Requêtes communes aux entités rattachées à un projet (projets eux-mêmes, tâches).
 *
 * Le mentor est le propriétaire du projet ; le stagiaire est membre du groupe
 * affecté au projet (ou stagiaire du stage pour les projets historiques).
 */
trait FiltreParAccesAuProjet
{
    abstract protected function requete(): Builder;

    /** Relation menant au projet, ou null si la requête porte directement sur les projets. */
    abstract protected function relationProjet(): ?string;

    public function findForMentor(int $mentorId): Collection
    {
        return $this->pourMentor($mentorId)->get();
    }

    public function paginateAll(int $parPage = 5): LengthAwarePaginator
    {
        return $this->paginer($this->requete(), $parPage);
    }

    public function paginateForStagiaire(int $stagiaireId, int $parPage = 5): LengthAwarePaginator
    {
        return $this->paginer($this->pourStagiaire($stagiaireId), $parPage);
    }

    public function paginateForMentor(int $mentorId, int $parPage = 5): LengthAwarePaginator
    {
        return $this->paginer($this->pourMentor($mentorId), $parPage);
    }

    protected function pourMentor(int $mentorId): Builder
    {
        return $this->filtrerProjet(fn (Builder $projets) => $projets->duMentor($mentorId));
    }

    protected function pourStagiaire(int $stagiaireId): Builder
    {
        return $this->filtrerProjet(fn (Builder $projets) => $projets->duStagiaire($stagiaireId));
    }

    private function filtrerProjet(callable $restriction): Builder
    {
        $query = $this->requete();
        $relation = $this->relationProjet();

        if ($relation === null) {
            $restriction($query);

            return $query;
        }

        return $query->whereHas($relation, $restriction);
    }

    protected function paginer(Builder $query, int $parPage): LengthAwarePaginator
    {
        return $query->orderByDesc('id')->paginate($parPage)->withQueryString();
    }
}
