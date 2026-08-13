<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Services\TaskService;

class TaskController extends Controller
{
    public function __construct(
        protected TaskService $taskService,
        protected TaskRepositoryInterface $tasks
    ) {}

    public function index()
    {
        $tasks = $this->tasks->all();
        return view('tasks.index', compact('tasks'));
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
        $this->taskService->changerStatut($task, $request->validated('statut'));

        return back()->with('success', 'Statut mis à jour.');
    }
}
