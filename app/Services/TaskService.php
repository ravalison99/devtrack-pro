<?php

namespace App\Services;

use App\Events\TaskCreated;
use App\Events\TaskStatusChanged;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TaskService
{
    protected const TRANSITIONS_AUTORISEES = [
        'a_faire' => ['en_cours'],
        'en_cours' => ['en_revue', 'a_faire'],
        'en_revue' => ['termine', 'en_cours'],
        'termine' => [],
    ];

    public function __construct(protected TaskRepositoryInterface $tasks) {}

    public function creer(array $data): Task
    {
        $task = $this->tasks->create($data);

        TaskCreated::dispatch($task);

        return $task;
    }

    public function transitionsAutoriseesPour(Task $task, User $user): array
    {
        $transitions = self::TRANSITIONS_AUTORISEES[$task->statut] ?? [];

        if ($user->isStagiaire()) {
            $transitions = array_values(array_filter($transitions, fn (string $statut) => $statut !== 'termine'));
        }

        return $transitions;
    }

    public function changerStatut(Task $task, string $nouveauStatut): Task
    {
        $transitionsPossibles = self::TRANSITIONS_AUTORISEES[$task->statut] ?? [];

        if (! in_array($nouveauStatut, $transitionsPossibles, true)) {
            throw ValidationException::withMessages([
                'statut' => "Impossible de passer de '{$task->statut}' à '{$nouveauStatut}'.",
            ]);
        }

        $ancienStatut = $task->statut;
        $task = $this->tasks->updateStatut($task, $nouveauStatut);

        TaskStatusChanged::dispatch($task, $ancienStatut, $nouveauStatut);

        return $task;
    }

    public function ajouterPieceJointe(Task $task, UploadedFile $fichier): \App\Models\Attachment
    {
        $chemin = $fichier->store('attachments', 'local');

        return $task->attachments()->create([
            'nom_fichier' => $fichier->getClientOriginalName(),
            'chemin' => $chemin,
        ]);
    }
}
