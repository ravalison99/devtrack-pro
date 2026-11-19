<?php

namespace App\Events;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FichierRetire
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Project $project,
        public string $titreDocument,
        public string $nomFichier,
        public User $auteur
    ) {}
}
