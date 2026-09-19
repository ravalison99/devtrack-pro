<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Services\TaskService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class KanbanController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected TaskRepositoryInterface $tasks,
        protected ProjectRepositoryInterface $projects,
        protected TaskService $taskService
    ) {}

    public function show(int $projectId)
    {
        $project = $this->projects->findById($projectId);

        $this->authorize('view', $project);

        $tasks = $this->tasks->findByProject($projectId);
        $utilisateur = auth()->user();

        $colonnes = [
            'a_faire' => $tasks->where('statut', 'a_faire'),
            'en_cours' => $tasks->where('statut', 'en_cours'),
            'en_revue' => $tasks->where('statut', 'en_revue'),
            'termine' => $tasks->where('statut', 'termine'),
        ];

        $transitionsParTache = $tasks->mapWithKeys(
            fn (Task $task) => [$task->id => $this->taskService->transitionsAutoriseesPour($task, $utilisateur)]
        );

        return view('kanban.show', compact('project', 'colonnes', 'transitionsParTache'));
    }
}
