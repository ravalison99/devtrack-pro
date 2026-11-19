<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Services\ProjectService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ProjectService $projectService,
        protected ProjectRepositoryInterface $projects,
        protected TaskRepositoryInterface $tasks
    ) {}

    public function index()
    {
        $utilisateur = auth()->user();

        $projects = match (true) {
            $utilisateur->isAdmin() => $this->projects->paginateAll(),
            $utilisateur->isMentor() => $this->projects->paginateForMentor($utilisateur->id),
            default => $this->projects->paginateForStagiaire($utilisateur->id),
        };

        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        $this->authorize('create', Project::class);

        return view('projects.create');
    }

    public function store(StoreProjectRequest $request)
    {
        $this->projectService->creer(
            $request->user(),
            $request->validated(),
            $request->file('cahier_charges')
        );

        return redirect()
            ->route('projects.index')
            ->with('success', 'Projet créé avec succès.');
    }

    public function show(int $projectId)
    {
        $project = $this->projects->findWithDetails($projectId);
        abort_if($project === null, 404);

        $this->authorize('view', $project);

        $utilisateur = auth()->user();

        // Ceux qui répartissent les tâches (mentor, chef de groupe) et l'administrateur voient l'avancement de tout le projet ;
        // un membre voit sa propre progression.
        $voitToutLeProjet = $utilisateur->isAdmin() || $utilisateur->can('gererTaches', $project);
        $progression = $voitToutLeProjet
            ? $this->tasks->progressionProjet($project->id)
            : $this->tasks->progressionDe($utilisateur->id);

        return view('projects.show', compact('project', 'progression', 'voitToutLeProjet'));
    }

    /** Le chemin de stockage n'est jamais exposé : le fichier transite par ce contrôleur après autorisation. */
    public function downloadCahierDesCharges(int $projectId)
    {
        $project = $this->projects->findById($projectId);
        abort_if($project === null || ! $project->aUnCahierDesCharges(), 404);

        $this->authorize('voirCahierDesCharges', $project);

        abort_unless(Storage::disk('local')->exists($project->cahier_charges_chemin), 404);

        return Storage::disk('local')->download($project->cahier_charges_chemin, $project->cahier_charges_nom);
    }
}
