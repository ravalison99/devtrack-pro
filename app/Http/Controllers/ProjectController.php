<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Models\Stage;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Services\ProjectService;

class ProjectController extends Controller
{
    public function __construct(
        protected ProjectService $projectService,
        protected ProjectRepositoryInterface $projects
    ) {}

    public function index()
    {
        $projects = $this->projects->all();
        return view('projects.index', compact('projects'));
    }

    public function create(Stage $stage)
    {
        return view('projects.create', compact('stage'));
    }

    public function store(StoreProjectRequest $request)
    {
        $stage = Stage::findOrFail($request->validated('stage_id'));

        $this->projectService->creer($request->validated(), $stage);

        return redirect()
            ->route('projects.index')
            ->with('success', 'Projet créé avec succès.');
    }
}
