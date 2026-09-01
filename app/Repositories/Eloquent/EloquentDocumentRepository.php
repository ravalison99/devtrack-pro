<?php

namespace App\Repositories\Eloquent;

use App\Models\Document;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentDocumentRepository implements DocumentRepositoryInterface
{
    public function findById(int $id): ?Document
    {
        return Document::with('versions')->find($id);
    }

    public function findByUtilisateur(int $utilisateurId): Collection
    {
        return Document::where('utilisateur_id', $utilisateurId)->get();
    }

    public function create(array $data): Document
    {
        return Document::create($data);
    }
}
