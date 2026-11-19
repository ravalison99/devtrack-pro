<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EloquentProjectRepository implements ProjectRepositoryInterface
{
    use FiltreParAccesAuProjet;

    public function all(): Collection
    {
        return $this->requete()->get();
    }

    public function findById(int $id): ?Project
    {
        return $this->requete()->find($id);
    }

    /** Détail d'un projet : groupe, membres, cahier des charges et rapports déposés (versions incluses). */
    public function findWithDetails(int $id): ?Project
    {
        return $this->requete()
            ->with([
                'mentor',
                'documents' => fn ($q) => $q->orderByDesc('id'),
                'documents.versions' => fn ($q) => $q->orderByDesc('numero_version'),
                'documents.versions.utilisateur',
            ])
            ->find($id);
    }

    public function create(array $data): Project
    {
        return Project::create($data);
    }

    public function countForStagiaire(int $stagiaireId): int
    {
        return Project::duStagiaire($stagiaireId)->count();
    }

    public function count(): int
    {
        return Project::count();
    }

    /** Projets confiés à un groupe dont le stagiaire est chef. */
    public function findForChef(int $chefId): Collection
    {
        return $this->requete()->duChef($chefId)->orderBy('nom')->get();
    }

    public function findNonAffectesForMentor(int $mentorId): Collection
    {
        return $this->requete()
            ->duMentor($mentorId)
            ->nonAffecte()
            ->orderBy('nom')
            ->get();
    }

    protected function requete(): Builder
    {
        return Project::with(['stage', 'groupe.chef', 'groupe.membres']);
    }

    protected function relationProjet(): ?string
    {
        return null;
    }
}
