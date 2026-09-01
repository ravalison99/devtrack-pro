<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentService $documentService,
        protected DocumentRepositoryInterface $documents
    ) {}

    public function index()
    {
        $documents = $this->documents->findByUtilisateur(auth()->id());
        return view('documents.index', compact('documents'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'categorie' => ['nullable', 'string', 'max:100'],
            'fichier' => ['required', 'file', 'max:5120', 'mimes:pdf,doc,docx,png,jpg,jpeg'],
        ]);

        $this->documentService->deposer(auth()->user(), $data['titre'], $data['categorie'] ?? null, $request->file('fichier'));

        return redirect()->route('documents.index')->with('success', 'Document déposé avec succès.');
    }

    public function download(int $documentId, int $versionId)
    {
        $document = $this->documents->findById($documentId);

        abort_unless(auth()->user()->can('view', $document), 403);

        $version = $document->versions->firstWhere('id', $versionId);

        return Storage::disk('local')->download($version->fichier);
    }
}
