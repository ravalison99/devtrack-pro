<?php

namespace App\Repositories\Contracts;

use App\Models\Stage;
use Illuminate\Database\Eloquent\Collection;

interface StageRepositoryInterface
{
    public function all(): Collection;
    public function findById(int $id): ?Stage;
    public function findByStagiaire(int $stagiaireId): ?Stage;
    public function findByMentor(int $mentorId): Collection;
    public function create(array $data): Stage;
    public function update(Stage $stage, array $data): Stage;
}
