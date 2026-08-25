<?php

namespace App\Services;

use App\Events\TaskStatusChanged;
use App\Models\Task;
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
        return $this->tasks->create($data);
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
