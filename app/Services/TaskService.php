<?php

namespace App\Services;

use App\Events\CommentaireAjoute;
use App\Events\PieceJointeAjoutee;
use App\Events\TaskCreated;
use App\Events\TaskStatusChanged;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Http\UploadedFile;
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

    /** Dépôt d'un fichier sur la tâche (ex. document rendu par le stagiaire), avec son auteur. */
    public function ajouterPieceJointe(Task $task, UploadedFile $fichier, ?User $auteur = null): Attachment
    {
        $chemin = $fichier->store('attachments', 'local');

        $piece = $task->attachments()->create([
            'utilisateur_id' => $auteur?->id,
            'nom_fichier' => $fichier->getClientOriginalName(),
            'chemin' => $chemin,
        ]);

        PieceJointeAjoutee::dispatch($piece);

        return $piece;
    }

    /** Message laissé sur la tâche (discussion entre stagiaire, chef de groupe, mentor...). */
    public function ajouterCommentaire(Task $task, User $auteur, string $contenu): Comment
    {
        $commentaire = $task->comments()->create([
            'utilisateur_id' => $auteur->id,
            'contenu' => $contenu,
        ]);

        CommentaireAjoute::dispatch($commentaire);

        return $commentaire;
    }
}
