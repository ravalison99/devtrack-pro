<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\StageRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\Contracts\WeeklyReportRepositoryInterface;

class DashboardService
{
    public function __construct(
        protected StageRepositoryInterface $stages,
        protected TaskRepositoryInterface $tasks,
        protected WeeklyReportRepositoryInterface $reports
    ) {}

    public function indicateursPour(User $user): array
    {
        return match (true) {
            $user->isAdmin() => $this->indicateursAdmin(),
            $user->isMentor() => $this->indicateursMentor($user),
            default => $this->indicateursStagiaire($user),
        };
    }

    protected function indicateursAdmin(): array
    {
        return [
            'stages_actifs' => $this->stages->countActifs(),
        ];
    }

    protected function indicateursMentor(User $mentor): array
    {
        return [
            'rapports_recus' => $this->reports->countForMentor($mentor->id),
        ];
    }

    protected function indicateursStagiaire(User $stagiaire): array
    {
        $tachesParStatut = $this->tasks->countByStatutForStagiaire($stagiaire->id);

        return [
            'taches_a_faire' => $tachesParStatut['a_faire'] ?? 0,
            'taches_en_cours' => $tachesParStatut['en_cours'] ?? 0,
            'taches_en_revue' => $tachesParStatut['en_revue'] ?? 0,
            'taches_terminees' => $tachesParStatut['termine'] ?? 0,
        ];
    }
}
