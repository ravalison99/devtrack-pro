<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Stage;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Validation\ValidationException;

class ProjectService
{
    public function __construct(protected ProjectRepositoryInterface $projects) {}

    public function creer(array $data, Stage $stage): Project
    {
        $this->verifierStageActif($stage);

        return $this->projects->create($data);
    }

    public function archiver(Project $project): Project
    {
        return $this->projects->update($project, ['archive' => true]);
    }

    protected function verifierStageActif(Stage $stage): void
    {
        if ($stage->statut !== 'en_cours' && $stage->statut !== 'planifie') {
            throw ValidationException::withMessages([
                'stage_id' => 'Impossible de créer un projet sur un stage terminé ou annulé.',
            ]);
        }
    }
}
