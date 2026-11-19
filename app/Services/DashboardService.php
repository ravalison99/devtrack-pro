<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Repositories\Contracts\JournalRepositoryInterface;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\StageRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;

class DashboardService
{
    public function __construct(
        protected StageRepositoryInterface $stages,
        protected TaskRepositoryInterface $tasks,
        protected UserRepositoryInterface $users,
        protected ProjectRepositoryInterface $projects,
        protected DocumentRepositoryInterface $documents,
        protected JournalRepositoryInterface $journal
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

    /**
     * Accueil du stagiaire : son projet et son équipe, ce qu'il doit faire maintenant, sa progression,
     * son journal du jour et l'activité récente qui le concerne.
     */
    protected function indicateursStagiaire(User $stagiaire): array
    {
        $tachesParStatut = $this->tasks->countByStatutForStagiaire($stagiaire->id);
        $stage = $this->stages->findByStagiaire($stagiaire->id);
        $projet = $this->projects->findPrincipalForStagiaire($stagiaire->id);
        $groupe = $projet?->groupe;
        $estChef = $groupe !== null && $groupe->chef_id === $stagiaire->id;

        return [
            'stage' => $stage,
            'mentor' => $projet?->mentor ?? $stage?->mentor,
            'projet' => $projet,
            'groupe' => $groupe,
            'est_chef' => $estChef,
            'mes_projets' => $this->projects->countForStagiaire($stagiaire->id),
            'taches_a_faire' => $tachesParStatut['a_faire'] ?? 0,
            'taches_en_cours' => $tachesParStatut['en_cours'] ?? 0,
            'taches_en_revue' => $tachesParStatut['en_revue'] ?? 0,
            'taches_terminees' => $tachesParStatut['termine'] ?? 0,
            'mes_documents' => $this->documents->countForStagiaire($stagiaire->id),

            'mes_taches' => $this->tasks->aFaireMaintenant($stagiaire->id),
            'progression' => $this->tasks->progressionDe($stagiaire->id),
            // Le chef de groupe suit aussi l'avancement de tout le projet.
            'progression_equipe' => ($estChef && $projet) ? $this->tasks->progressionProjet($projet->id) : null,
            'journal_rempli' => $this->journal->findByStagiaireAndDate($stagiaire->id, today()->toDateString()) !== null,
            'activite' => $stagiaire->notifications()->latest()->limit(5)->get(),
        ];
    }
}
