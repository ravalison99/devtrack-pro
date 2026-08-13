<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use App\Models\Stage;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\StageRepositoryInterface;
use App\Services\ProjectService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

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
        $projects = $this->projects->all();
        return view('projects.index', compact('projects'));
    }

    public function create(Stage $stage)
    {
        $this->authorize('create', [Project::class, $stage]);

        return view('projects.create', compact('stage'));
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
