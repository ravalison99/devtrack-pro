<?php

namespace App\Services;

use App\Events\GroupeCree;
use App\Models\Groupe;
use App\Models\User;
use App\Repositories\Contracts\GroupeRepositoryInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GroupeService
{
    public function __construct(protected GroupeRepositoryInterface $groupes) {}

    /**
     * Crée le groupe, ses membres, son chef et l'association au projet en une seule transaction.
     *
     * @param  array{nom: string, membres: array<int>, chef_id: int, project_id: int}  $data
     */
    public function creer(User $mentor, array $data): Groupe
    {
        $membres = array_values(array_unique(array_map('intval', $data['membres'])));

        // Garde-fous métier (déjà validés par la requête, revérifiés ici pour tout autre appelant).
        if (count($membres) < 1 || count($membres) > Groupe::MAX_MEMBRES) {
            throw ValidationException::withMessages([
                'membres' => 'Un groupe doit contenir de 1 à '.Groupe::MAX_MEMBRES.' stagiaires.',
            ]);
        }

        if (! in_array((int) $data['chef_id'], $membres, true)) {
            throw ValidationException::withMessages([
                'chef_id' => 'Le chef de groupe doit être l\'un des membres du groupe.',
            ]);
        }

        try {
            $groupe = DB::transaction(function () use ($mentor, $data, $membres) {
                $groupe = $this->groupes->create([
                    'nom' => $data['nom'],
                    'mentor_id' => $mentor->id,
                    'chef_id' => (int) $data['chef_id'],
                    'project_id' => (int) $data['project_id'],
                ]);

                $groupe->membres()->attach($membres);

                return $groupe;
            });

            GroupeCree::dispatch($groupe);

            return $groupe;
        } catch (UniqueConstraintViolationException) {
            // Filet de sécurité en cas de création simultanée : un projet / un stagiaire déjà pris.
            throw ValidationException::withMessages([
                'project_id' => 'Le projet ou l\'un des stagiaires vient d\'être affecté à un autre groupe.',
            ]);
        }
    }
}
