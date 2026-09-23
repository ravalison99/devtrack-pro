<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Models\Project;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Services\DocumentService;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentService $documentService,
        protected DocumentRepositoryInterface $documents,
        protected ProjectRepositoryInterface $projects
    ) {}

    public function index()
    {
        $documents = $this->documents->paginateByUtilisateur(auth()->id());
        return view('documents.index', compact('documents'));
    }

    public function store(StoreDocumentRequest $request)
    {
        $data = $request->validated();

        $project = ! empty($data['project_id']) ? $this->projects->findById((int) $data['project_id']) : null;

        $this->documentService->deposer(auth()->user(), $data['titre'], $data['categorie'] ?? null, $request->file('fichier'), $project);

        if ($project !== null) {
            return redirect()->route('kanban.show', $project->id)->with('success', 'Document déposé avec succès.');
        }

        return redirect()->route('documents.index')->with('success', 'Document déposé avec succès.');
    }

    public function byProject(Project $project)
    {
        $utilisateur = auth()->user();
        $stage = $project->stage;

        abort_unless(
            $utilisateur->isAdmin() || $utilisateur->id === $stage->mentor_id || $utilisateur->id === $stage->stagiaire_id,
            403
        );

        $documents = $this->documents->findByProject($project->id);

        return view('documents.byProject', compact('project', 'documents'));
    }

    public function download(int $documentId, int $versionId)
    {
        $document = $this->documents->findById($documentId);

        abort_unless(auth()->user()->can('view', $document), 403);

        $version = $document->versions->firstWhere('id', $versionId);

        return Storage::disk('local')->download($version->fichier);
    }
}
