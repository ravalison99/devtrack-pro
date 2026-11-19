<?php

namespace App\Repositories\Contracts;

use App\Models\Document;

interface DocumentRepositoryInterface
{
    public function findById(int $id): ?Document;

    public function countForStagiaire(int $stagiaireId): int;

    public function findByProjectTitreEtType(int $projectId, string $titre, string $typeRapport): ?Document;

    public function create(array $data): Document;
}
