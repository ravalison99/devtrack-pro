<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    public function __construct(protected DocumentRepositoryInterface $documents) {}

    public function deposer(User $utilisateur, string $titre, ?string $categorie, UploadedFile $fichier): Document
    {
        $document = $this->documents->create([
            'utilisateur_id' => $utilisateur->id,
            'titre' => $titre,
            'categorie' => $categorie,
        ]);

        $this->ajouterVersion($document, $fichier);

        return $document;
    }

    public function ajouterVersion(Document $document, UploadedFile $fichier): DocumentVersion
    {
        $derniereVersion = $document->derniereVersion();
        $prochainNumero = $derniereVersion ? $derniereVersion->numero_version + 1 : 1;

        $chemin = $fichier->store('documents', 'local');

        return $document->versions()->create([
            'numero_version' => $prochainNumero,
            'fichier' => $chemin,
        ]);
    }
}
