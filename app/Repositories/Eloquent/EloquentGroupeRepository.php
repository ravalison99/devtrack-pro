<?php

namespace App\Repositories\Eloquent;

use App\Models\Groupe;
use App\Repositories\Contracts\GroupeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentGroupeRepository implements GroupeRepositoryInterface
{
    public function create(array $data): Groupe
    {
        return Groupe::create($data);
    }

    public function paginateForMentor(int $mentorId, int $parPage = 5): LengthAwarePaginator
    {
        return Groupe::with(['chef', 'membres', 'project'])
            ->where('mentor_id', $mentorId)
            ->orderByDesc('id')
            ->paginate($parPage)
            ->withQueryString();
    }
}
