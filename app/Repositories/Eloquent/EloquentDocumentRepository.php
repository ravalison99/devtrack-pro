<?php

namespace App\Repositories\Eloquent;

use App\Models\Document;
use App\Repositories\Contracts\DocumentRepositoryInterface;

class EloquentDocumentRepository implements DocumentRepositoryInterface
{
    public function findById(int $id): ?Document
    {
        return Document::with(['versions', 'project.groupe'])->find($id);
    }

    /** Documents des projets attribués à un stagiaire (groupe ou stage historique). */
    public function countForStagiaire(int $stagiaireId): int
    {
        return Document::whereHas('project', fn ($q) => $q->duStagiaire($stagiaireId))->count();
    }

    /**
     * Identité d'un « même document » : projet + nom (insensible à la casse) + type de rapport.
     * La catégorie n'entre volontairement pas en compte.
     */
    public function findByProjectTitreEtType(int $projectId, string $titre, string $typeRapport): ?Document
    {
        return Document::where('project_id', $projectId)
            ->where('type_rapport', $typeRapport)
            ->whereRaw('LOWER(titre) = ?', [mb_strtolower($titre)])
            ->lockForUpdate()
            ->first();
    }

    public function create(array $data): Document
    {
        return Document::create($data);
    }
}
