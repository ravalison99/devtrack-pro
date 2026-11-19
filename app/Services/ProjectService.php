<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProjectService
{
    public function __construct(protected ProjectRepositoryInterface $projects) {}

    /**
     * Crée un projet pour le mentor, avec son cahier des charges.
     * Aucun groupe n'est créé ni associé ici : l'affectation se fait à la création du groupe.
     */
    public function creer(User $mentor, array $data, UploadedFile $cahierDesCharges): Project
    {
        // Disque « local » = stockage privé (jamais servi directement par le serveur web).
        $chemin = $cahierDesCharges->store('projets/cahiers-des-charges', 'local');

        try {
            return DB::transaction(fn () => $this->projects->create([
                'mentor_id' => $mentor->id,
                'nom' => $data['nom'],
                'description' => $data['description'] ?? null,
                'cahier_charges_nom' => $cahierDesCharges->getClientOriginalName(),
                'cahier_charges_chemin' => $chemin,
                'cahier_charges_taille' => $cahierDesCharges->getSize(),
            ]));
        } catch (Throwable $e) {
            Storage::disk('local')->delete($chemin);

            throw $e;
        }
    }
}
