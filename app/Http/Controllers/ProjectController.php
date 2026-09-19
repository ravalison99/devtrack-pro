<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use App\Models\Stage;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\StageRepositoryInterface;
use App\Services\ProjectService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ProjectService $projectService,
        protected ProjectRepositoryInterface $projects,
        protected StageRepositoryInterface $stages
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

    public function create(Request $request, ?Stage $stage = null)
    {
        if ($stage !== null) {
            $this->authorize('create', [Project::class, $stage]);

            return view('projects.create', compact('stage'));
        }

        $utilisateur = auth()->user();

        abort_unless($utilisateur->isMentor(), 403);

        $mesStages = $this->stages->findByMentor($utilisateur->id);

        $stagePreselectionnee = null;
        if ($request->filled('stage_id')) {
            $stagePreselectionnee = $mesStages->firstWhere('id', (int) $request->input('stage_id'));
        }

        return view('projects.create', ['stage' => $stagePreselectionnee, 'stages' => $mesStages]);
    }

    public function store(StoreProjectRequest $request)
    {
        $stage = $this->stages->findById((int) $request->validated('stage_id'));

        $this->projectService->creer($request->validated(), $stage);

        return redirect()
            ->route('projects.index')
            ->with('success', 'Projet créé avec succès.');
    }
}
