<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\Stage;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\StageRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Services\DocumentService;
use App\Services\ProjectService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ProjectService $projectService,
        protected ProjectRepositoryInterface $projects,
        protected StageRepositoryInterface $stages,
        protected DocumentService $documentService,
        protected TaskRepositoryInterface $tasks,
        protected DocumentRepositoryInterface $documents
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

        DB::transaction(function () use ($request, $stage) {
            $project = $this->projectService->creer($request->validated(), $stage);

            if ($request->hasFile('fichier')) {
                $titreDocument = $request->validated('document_titre') ?: 'Cahier des charges — '.$project->nom;

                $this->documentService->deposer(
                    auth()->user(),
                    $titreDocument,
                    null,
                    $request->file('fichier'),
                    $project
                );
            }
        });

        return redirect()
            ->route('projects.index')
            ->with('success', 'Projet créé avec succès.');
    }

    public function show(Project $project)
    {
        $this->authorize('view', $project);

        $tasks = $this->tasks->findByProject($project->id);
        $documents = $this->documents->findByProject($project->id);

        return view('projects.show', compact('project', 'tasks', 'documents'));
    }

    public function edit(Project $project)
    {
        $this->authorize('update', $project);

        return view('projects.edit', compact('project'));
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $this->projectService->modifier($project, $request->validated());

        return redirect()
            ->route('projects.index')
            ->with('success', 'Projet modifié avec succès.');
    }

    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $this->projectService->supprimer($project);

        return redirect()
            ->route('projects.index')
            ->with('success', 'Projet et tâches associées supprimés avec succès.');
    }
}
