<?php

namespace App\Repositories\Contracts;

use App\Models\Groupe;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GroupeRepositoryInterface
{
    public function create(array $data): Groupe;

    public function paginateForMentor(int $mentorId, int $parPage = 5): LengthAwarePaginator;
}
