<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    protected function creerProjet(): Project
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        $stage = Stage::create([
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
        ]);

        return Project::create([
            'stage_id' => $stage->id,
            'nom' => 'Projet de test',
        ]);
    }

    public function test_une_tache_peut_etre_creee(): void
    {
        $project = $this->creerProjet();
        $mentor = $project->stage->mentor;

        $response = $this->actingAs($mentor)->post('/tasks', [
            'project_id' => $project->id,
            'titre' => 'Nouvelle tâche',
            'priorite' => 'haute',
        ]);

        $response->assertRedirect('/tasks');
        $this->assertDatabaseHas('tasks', ['titre' => 'Nouvelle tâche']);
    }

    public function test_une_transition_de_statut_valide_est_acceptee(): void
    {
        $project = $this->creerProjet();
        $mentor = $project->stage->mentor;
        $task = Task::create(['project_id' => $project->id, 'titre' => 'Tâche test', 'statut' => 'a_faire']);

        $response = $this->actingAs($mentor)->patch("/tasks/{$task->id}/status", [
            'statut' => 'en_cours',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'statut' => 'en_cours']);
    }

    public function test_une_transition_de_statut_invalide_est_refusee(): void
    {
        $project = $this->creerProjet();
        $mentor = $project->stage->mentor;
        $task = Task::create(['project_id' => $project->id, 'titre' => 'Tâche test', 'statut' => 'a_faire']);

        $response = $this->actingAs($mentor)->patch("/tasks/{$task->id}/status", [
            'statut' => 'termine',
        ]);

        $response->assertSessionHasErrors('statut');
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'statut' => 'a_faire']);
    }

    public function test_une_piece_jointe_valide_est_acceptee(): void
    {
        Storage::fake('local');

        $project = $this->creerProjet();
        $mentor = $project->stage->mentor;
        $task = Task::create(['project_id' => $project->id, 'titre' => 'Tâche test']);

        $fichier = UploadedFile::fake()->create('rapport.pdf', 500);

        $response = $this->actingAs($mentor)->post("/tasks/{$task->id}/attachments", [
            'fichier' => $fichier,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('attachments', ['task_id' => $task->id, 'nom_fichier' => 'rapport.pdf']);
    }

    public function test_un_fichier_de_type_non_autorise_est_refuse(): void
    {
        Storage::fake('local');

        $project = $this->creerProjet();
        $mentor = $project->stage->mentor;
        $task = Task::create(['project_id' => $project->id, 'titre' => 'Tâche test']);

        $fichier = UploadedFile::fake()->create('archive.rar', 500);

        $response = $this->actingAs($mentor)->post("/tasks/{$task->id}/attachments", [
            'fichier' => $fichier,
        ]);

        $response->assertSessionHasErrors('fichier');
        $this->assertDatabaseMissing('attachments', ['task_id' => $task->id]);
    }
}
