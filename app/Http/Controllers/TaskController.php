<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\Attachment;
use App\Models\Task;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Services\TaskService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected TaskService $taskService,
        protected TaskRepositoryInterface $tasks,
        protected ProjectRepositoryInterface $projects
    ) {}

    public function index()
    {
        $utilisateur = auth()->user();

        $tasks = match (true) {
            $utilisateur->isAdmin() => $this->tasks->paginateAll(),
            $utilisateur->isMentor() => $this->tasks->paginateForMentor($utilisateur->id),
            default => $this->tasks->paginateForStagiaire($utilisateur->id),
        };

        $transitionsParTache = collect($tasks->items())->mapWithKeys(
            fn (Task $task) => [$task->id => $this->taskService->transitionsAutoriseesPour($task, $utilisateur)]
        );

        return view('tasks.index', compact('tasks', 'transitionsParTache'));
    }

    public function show(Task $task)
    {
        $this->authorize('view', $task);

        $task = $this->tasks->findById($task->id);

        return view('tasks.show', compact('task'));
    }

    public function create(Request $request)
    {
        $utilisateur = auth()->user();

        // Administrateur, mentor, ou stagiaire chef de groupe (il répartit les tâches de son projet).
        abort_unless($utilisateur->isAdmin() || $utilisateur->isMentor() || $utilisateur->estChefDeGroupe(), 403);

        $projetSelectionne = null;

        if ($request->filled('project_id')) {
            $projetSelectionne = $this->projects->findById((int) $request->input('project_id'));
            abort_if($projetSelectionne === null, 404);
            $this->authorize('gererTaches', $projetSelectionne);
        }

        $projects = match (true) {
            $utilisateur->isAdmin() => $this->projects->all(),
            $utilisateur->isMentor() => $this->projects->findForMentor($utilisateur->id),
            default => $this->projects->findForChef($utilisateur->id),
        };

        return view('tasks.create', compact('projects', 'projetSelectionne'));
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
        // Une pièce jointe ne peut être ajoutée qu'à une tâche que l'on a le droit de voir.
        $this->authorize('view', $task);

        $this->taskService->ajouterPieceJointe($task, $request->file('fichier'), $request->user());

        return back()->with('success', 'Pièce jointe ajoutée.');
    }

    /** Le chemin de stockage n'est jamais exposé : le fichier transite ici après contrôle d'accès à la tâche. */
    public function downloadAttachment(Task $task, Attachment $attachment)
    {
        $this->authorize('voirPiecesJointes', $task);

        abort_unless($attachment->task_id === $task->id, 404);
        abort_unless(Storage::disk('local')->exists($attachment->chemin), 404);

        return Storage::disk('local')->download($attachment->chemin, $attachment->nom_fichier);
    }

    public function storeComment(StoreCommentRequest $request, Task $task)
    {
        $this->taskService->ajouterCommentaire($task, $request->user(), $request->validated('contenu'));

        return back()->with('success', 'Commentaire ajouté.');
    }
}
