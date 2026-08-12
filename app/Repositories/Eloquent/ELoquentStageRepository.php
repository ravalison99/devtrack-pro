<?php

namespace App\Repositories\Eloquent;

use App\Models\Stage;
use App\Repositories\Contracts\StageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentStageRepository implements StageRepositoryInterface
{
    public function all(): Collection
    {
        return Stage::with(['stagiaire', 'mentor'])->get();
    }

    public function findById(int $id): ?Stage
    {
        return Stage::with(['stagiaire', 'mentor'])->find($id);
    }

    public function findByStagiaire(int $stagiaireId): ?Stage
    {
        return Stage::where('stagiaire_id', $stagiaireId)
            ->whereIn('statut', ['planifie', 'en_cours'])
            ->first();
    }

    public function findByMentor(int $mentorId): Collection
    {
        return Stage::where('mentor_id', $mentorId)->get();
    }

    public function create(array $data): Stage
    {
        return Stage::create($data);
    }

    public function update(Stage $stage, array $data): Stage
    {
        $stage->update($data);
        return $stage;
    }
}
