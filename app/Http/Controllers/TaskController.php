<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Services\TaskService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected TaskService $taskService,
        protected TaskRepositoryInterface $tasks
    ) {}

    public function index()
    {
        $tasks = $this->tasks->all();
        return view('tasks.index', compact('tasks'));
    }

    public function show(Task $task)
    {
        $task = $this->tasks->findById($task->id);
        return view('tasks.show', compact('task'));
    }

    public function store(StoreTaskRequest $request)
    {
        $this->taskService->creer($request->validated());

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Tâche créée avec succès.');
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task)
    {
        $nouveauStatut = $request->validated('statut');

        if (! auth()->user()->can('updateStatus', [$task, $nouveauStatut])) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Action non autorisée.'], 403);
            }
            abort(403);
        }

        $this->taskService->changerStatut($task, $nouveauStatut);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'statut' => $nouveauStatut]);
        }

        return back()->with('success', 'Statut mis à jour.');
    }

    public function storeAttachment(StoreAttachmentRequest $request, Task $task)
    {
        $this->taskService->ajouterPieceJointe($task, $request->file('fichier'));

        return back()->with('success', 'Pièce jointe ajoutée.');
    }
}
