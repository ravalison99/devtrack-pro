<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\StageRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\WeeklyReportRepositoryInterface;

class DashboardService
{
    public function __construct(
        protected StageRepositoryInterface $stages,
        protected TaskRepositoryInterface $tasks,
        protected WeeklyReportRepositoryInterface $reports,
        protected UserRepositoryInterface $users,
        protected ProjectRepositoryInterface $projects,
        protected DocumentRepositoryInterface $documents
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
            'total_utilisateurs' => $this->users->all()->count(),
            'total_stagiaires' => $this->users->countByRole('stagiaire'),
            'total_mentors' => $this->users->countByRole('mentor'),
            'total_stages' => $this->stages->all()->count(),
            'stages_en_cours' => $this->stages->countByStatut('en_cours'),
            'stages_termines' => $this->stages->countByStatut('termine'),
            'stages_annules' => $this->stages->countByStatut('annule'),
            'total_projets' => $this->projects->count(),
            'total_taches' => $this->tasks->count(),
        ];
    }

    protected function indicateursMentor(User $mentor): array
    {
        $stages = $this->stages->findByMentor($mentor->id);
        $taches = $this->tasks->findForMentor($mentor->id);

        $tachesEnRetard = $taches->filter(function ($tache) {
            return $tache->date_echeance !== null
                && $tache->date_echeance->isPast()
                && $tache->statut !== 'termine';
        })->count();

        return [
            'rapports_recus' => $this->reports->countForMentor($mentor->id),
            'mes_stagiaires' => $stages->pluck('stagiaire_id')->unique()->count(),
            'mes_stages' => $stages->count(),
            'stages_en_cours' => $stages->where('statut', 'en_cours')->count(),
            'mes_projets' => $this->projects->findForMentor($mentor->id)->count(),
            'mes_taches' => $taches->count(),
            'taches_en_cours' => $taches->where('statut', 'en_cours')->count(),
            'taches_terminees' => $taches->where('statut', 'termine')->count(),
            'taches_en_retard' => $tachesEnRetard,
        ];
    }

    protected function indicateursStagiaire(User $stagiaire): array
    {
        $tachesParStatut = $this->tasks->countByStatutForStagiaire($stagiaire->id);
        $stage = $this->stages->findByStagiaire($stagiaire->id);

        return [
            'stage' => $stage,
            'mentor' => $stage?->mentor,
            'mes_projets' => $stage?->projects->count() ?? 0,
            'taches_a_faire' => $tachesParStatut['a_faire'] ?? 0,
            'taches_en_cours' => $tachesParStatut['en_cours'] ?? 0,
            'taches_en_revue' => $tachesParStatut['en_revue'] ?? 0,
            'taches_terminees' => $tachesParStatut['termine'] ?? 0,
            'mes_documents' => $this->documents->findByUtilisateur($stagiaire->id)->count(),
        ];
    }
}
