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

        $utilisateur = auth()->user();

        // Administrateur et mentor du projet voient toutes les tâches ; un stagiaire seulement les siennes
        // (toutes celles du projet s'il est chef de groupe).
        $tasks = $utilisateur->isAdmin() || $utilisateur->id === $project->mentorId()
            ? $this->tasks->findByProject($projectId)
            : $this->tasks->findByProjectVisiblesPar($projectId, $utilisateur->id);

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
