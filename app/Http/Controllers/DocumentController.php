<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Services\DocumentCatalogueService;
use App\Services\DocumentService;
use App\Support\LigneDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentService $documentService,
        protected DocumentCatalogueService $catalogue,
        protected DocumentRepositoryInterface $documents
    ) {}

    /**
     * Catalogue des documents, avec recherche et filtres.
     * Mentor et administrateur voient tout ; un stagiaire ne voit que les documents de ses projets.
     */
    public function index(Request $request)
    {
        $filtres = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'origine' => ['nullable', Rule::in(array_keys(LigneDocument::ORIGINES))],
            'project_id' => ['nullable', 'integer'],
            'categorie' => ['nullable', Rule::in(Document::CATEGORIES)],
            'type_rapport' => ['nullable', Rule::in(array_keys(Document::TYPES_RAPPORT))],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
        ]);

        $utilisateur = $request->user();

        return view('documents.index', [
            'lignes' => $this->catalogue->rechercher($utilisateur, $filtres),
            'filtres' => $filtres,
            'projets' => $this->catalogue->projetsFiltrables($utilisateur),
            'origines' => LigneDocument::ORIGINES,
            'categories' => Document::CATEGORIES,
            'typesRapport' => Document::TYPES_RAPPORT,
        ]);
    }

    public function download(int $documentId, int $versionId)
    {
        $document = $this->documents->findById($documentId);
        abort_if($document === null, 404);

        abort_unless(auth()->user()->can('view', $document), 403);

        $version = $document->versions->firstWhere('id', $versionId);
        abort_if($version === null, 404);

        return Storage::disk('local')->download($version->fichier, $version->nom_fichier);
    }

    /** Retire une version de fichier : réservé au chef de groupe qui l'a déposée (DocumentVersionPolicy). */
    public function destroyVersion(int $documentId, int $versionId)
    {
        $document = $this->documents->findById($documentId);
        abort_if($document === null, 404);

        $version = $document->versions->firstWhere('id', $versionId);
        abort_if($version === null, 404);

        abort_unless(auth()->user()->can('delete', $version), 403);

        $projectId = $document->project_id;
        $this->documentService->retirerVersion($version, auth()->user());

        return redirect()
            ->route('projects.show', $projectId)
            ->with('success', 'Fichier retiré.');
    }
}
