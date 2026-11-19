<?php

namespace App\Services;

use App\Events\DocumentDepose;
use App\Events\FichierRetire;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    public function __construct(protected DocumentRepositoryInterface $documents) {}

    /**
     * Dépôt d'un document de projet par le chef de groupe, avec versionnement automatique :
     * même projet + même nom + même type de rapport => nouvelle version, sinon nouveau document.
     * Les versions précédentes sont conservées.
     */
    public function deposerPourProjet(
        User $utilisateur,
        Project $project,
        string $titre,
        string $categorie,
        string $typeRapport,
        UploadedFile $fichier
    ): DocumentVersion {
        $version = DB::transaction(function () use ($utilisateur, $project, $titre, $categorie, $typeRapport, $fichier) {
            $document = $this->documents->findByProjectTitreEtType($project->id, $titre, $typeRapport)
                ?? $this->documents->create([
                    'utilisateur_id' => $utilisateur->id,
                    'project_id' => $project->id,
                    'titre' => $titre,
                    'categorie' => $categorie,
                    'type_rapport' => $typeRapport,
                ]);

            return $this->ajouterVersion($document, $fichier, $utilisateur);
        });

        // Notification une fois le dépôt enregistré : le mentor et le groupe sont prévenus.
        DocumentDepose::dispatch($version->document, $version);

        return $version;
    }

    /**
     * Retire une version : son fichier est supprimé du stockage. Les autres versions sont conservées ;
     * si c'était la dernière, le document disparaît aussi (il n'aurait plus de contenu).
     */
    public function retirerVersion(DocumentVersion $version, User $auteur): void
    {
        $chemin = $version->fichier;
        $document = $version->document;
        $projet = $document->project;
        $titre = $document->titre;
        $nomFichier = $version->nom_fichier ?? basename($chemin);

        DB::transaction(function () use ($version, $document) {
            $version->delete();

            if (! $document->versions()->exists()) {
                $document->delete();
            }
        });

        Storage::disk('local')->delete($chemin);

        if ($projet !== null) {
            FichierRetire::dispatch($projet, $titre, $nomFichier, $auteur);
        }
    }

    public function ajouterVersion(Document $document, UploadedFile $fichier, ?User $utilisateur = null): DocumentVersion
    {
        $derniereVersion = $document->derniereVersion();
        $prochainNumero = $derniereVersion ? $derniereVersion->numero_version + 1 : 1;

        $chemin = $fichier->store('documents', 'local');

        return $document->versions()->create([
            'utilisateur_id' => $utilisateur?->id,
            'numero_version' => $prochainNumero,
            'fichier' => $chemin,
            'nom_fichier' => $fichier->getClientOriginalName(),
        ]);
    }
}
