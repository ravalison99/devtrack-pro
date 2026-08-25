<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;

class KanbanController extends Controller
{
    public function __construct(
        protected TaskRepositoryInterface $tasks,
        protected ProjectRepositoryInterface $projects
    ) {}

    public function show(int $projectId)
    {
        $project = $this->projects->findById($projectId);
        $tasks = $this->tasks->findByProject($projectId);

        $colonnes = [
            'a_faire' => $tasks->where('statut', 'a_faire'),
            'en_cours' => $tasks->where('statut', 'en_cours'),
            'en_revue' => $tasks->where('statut', 'en_revue'),
            'termine' => $tasks->where('statut', 'termine'),
        ];

        return view('kanban.show', compact('project', 'colonnes'));
    }
}
