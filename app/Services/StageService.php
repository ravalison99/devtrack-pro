<?php

namespace App\Services;

use App\Events\StageCreated;
use App\Events\StageStatusChanged;
use App\Models\Stage;
use App\Repositories\Contracts\StageRepositoryInterface;
use Illuminate\Validation\ValidationException;

class StageService
{
    protected const TRANSITIONS_AUTORISEES = [
        'planifie' => ['en_cours', 'annule'],
        'en_cours' => ['termine', 'annule'],
        'termine' => [],
        'annule' => [],
    ];

    protected const STATUTS_SUPPRIMABLES = ['termine', 'annule'];

    public function __construct(protected StageRepositoryInterface $stages) {}

    public function creer(array $data): Stage
    {
        $this->verifierStagiaireDisponible((int) $data['stagiaire_id']);

        $stage = $this->stages->create($data);

        StageCreated::dispatch($stage);

        return $stage;
    }

    public function changerStatut(Stage $stage, string $nouveauStatut): Stage
    {
        $transitionsPossibles = self::TRANSITIONS_AUTORISEES[$stage->statut] ?? [];

        if (! in_array($nouveauStatut, $transitionsPossibles, true)) {
            throw ValidationException::withMessages([
                'statut' => "Impossible de passer de '{$stage->statut}' à '{$nouveauStatut}'.",
            ]);
        }

        $ancienStatut = $stage->statut;
        $stage = $this->stages->update($stage, ['statut' => $nouveauStatut]);

        StageStatusChanged::dispatch($stage, $ancienStatut, $nouveauStatut);

        return $stage;
    }

    public function transitionsAutorisees(Stage $stage): array
    {
        return self::TRANSITIONS_AUTORISEES[$stage->statut] ?? [];
    }

    public function estSupprimable(Stage $stage): bool
    {
        return in_array($stage->statut, self::STATUTS_SUPPRIMABLES, true);
    }

    public function supprimer(Stage $stage): bool
    {
        if (! in_array($stage->statut, self::STATUTS_SUPPRIMABLES, true)) {
            throw ValidationException::withMessages([
                'statut' => 'Seul un stage terminé ou annulé peut être supprimé.',
            ]);
        }

        return $this->stages->delete($stage);
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
