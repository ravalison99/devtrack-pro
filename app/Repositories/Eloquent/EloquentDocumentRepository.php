<?php

namespace App\Repositories\Eloquent;

use App\Models\Document;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentDocumentRepository implements DocumentRepositoryInterface
{
    public function findById(int $id): ?Document
    {
        return Document::with(['versions', 'project.stage'])->find($id);
    }

    public function findByUtilisateur(int $utilisateurId): Collection
    {
        return Document::where('utilisateur_id', $utilisateurId)->get();
    }

    public function findByProject(int $projectId): Collection
    {
        return Document::with('versions')
            ->where('project_id', $projectId)
            ->orderByDesc('id')
            ->get();
    }

    public function paginateByUtilisateur(int $utilisateurId, int $parPage = 5): LengthAwarePaginator
    {
        return Document::with('versions')
            ->where('utilisateur_id', $utilisateurId)
            ->orderByDesc('id')
            ->paginate($parPage)
            ->withQueryString();
    }

    public function create(array $data): Document
    {
        return Document::create($data);
    }
}
