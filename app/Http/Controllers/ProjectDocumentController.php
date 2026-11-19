<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectDocumentRequest;
use App\Models\Project;
use App\Services\DocumentService;

class ProjectDocumentController extends Controller
{
    public function __construct(protected DocumentService $documentService) {}

    public function store(StoreProjectDocumentRequest $request, Project $project)
    {
        $data = $request->validated();

        $version = $this->documentService->deposerPourProjet(
            $request->user(),
            $project,
            $data['titre'],
            $data['categorie'],
            $data['type_rapport'],
            $request->file('fichier')
        );

        return redirect()
            ->route('projects.show', $project)
            ->with('success', "Document déposé (version {$version->numero_version}).");
    }
}
