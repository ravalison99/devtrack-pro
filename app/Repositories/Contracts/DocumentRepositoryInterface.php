<?php

namespace App\Repositories\Contracts;

use App\Models\Document;
use Illuminate\Database\Eloquent\Collection;

interface DocumentRepositoryInterface
{
    public function findById(int $id): ?Document;
    public function findByUtilisateur(int $utilisateurId): Collection;
    public function create(array $data): Document;
}
