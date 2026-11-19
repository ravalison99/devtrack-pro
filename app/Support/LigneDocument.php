<?php

namespace App\Support;

use App\Models\Document;
use App\Models\Groupe;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonInterface;

/**
 * Une ligne du catalogue des documents : un fichier, ce à quoi il est relié, et par qui il a été déposé.
 */
final class LigneDocument
{
    public const ORIGINES = [
        'projet' => 'Document de projet',
        'cahier' => 'Cahier des charges',
        'tache' => 'Pièce jointe de tâche',
        'personnel' => 'Document personnel',
    ];

    public function __construct(
        public readonly string $origine,
        public readonly string $titre,
        public readonly ?string $nomFichier,
        public readonly ?Project $projet,
        public readonly ?Groupe $groupe,
        public readonly ?Task $tache,
        public readonly ?User $deposant,
        public readonly ?string $categorie,
        public readonly ?string $typeRapport,
        public readonly ?int $versionActuelle,
        public readonly int $nombreVersions,
        public readonly ?CarbonInterface $date,
        public readonly ?string $urlTelechargement,
        public readonly ?Document $document = null,
    ) {}

    public function libelleOrigine(): string
    {
        return self::ORIGINES[$this->origine] ?? $this->origine;
    }
}
