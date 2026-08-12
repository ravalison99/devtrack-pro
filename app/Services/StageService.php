<?php

namespace App\Services;

use App\Models\Stage;
use App\Repositories\Contracts\StageRepositoryInterface;
use Illuminate\Validation\ValidationException;

class StageService
{
    public function __construct(protected StageRepositoryInterface $stages) {}

    public function creer(array $data): Stage
    {
        $this->verifierStagiaireDisponible((int) $data['stagiaire_id']);

        return $this->stages->create($data);
    }

    protected function verifierStagiaireDisponible(int $stagiaireId): void
    {
        $stageActif = $this->stages->findByStagiaire($stagiaireId);

        if ($stageActif !== null) {
            throw ValidationException::withMessages([
                'stagiaire_id' => 'Ce stagiaire a déjà un stage actif.',
            ]);
        }
    }
}
