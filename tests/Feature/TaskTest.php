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

    public function test_un_stagiaire_ne_voit_pas_les_taches_dun_autre_stagiaire(): void
    {
        $projetA = $this->creerProjet();
        $projetB = $this->creerProjet();
        Task::create(['project_id' => $projetA->id, 'titre' => 'Tâche A']);
        Task::create(['project_id' => $projetB->id, 'titre' => 'Tâche B']);

        $stagiaireA = $projetA->stage->stagiaire;

        $response = $this->actingAs($stagiaireA)->get('/tasks');

        $response->assertOk();
        $response->assertSee('Tâche A');
        $response->assertDontSee('Tâche B');
    }

    public function test_un_mentor_ne_voit_pas_les_taches_dun_autre_mentor(): void
    {
        $projetA = $this->creerProjet();
        $projetB = $this->creerProjet();
        Task::create(['project_id' => $projetA->id, 'titre' => 'Tâche A']);
        Task::create(['project_id' => $projetB->id, 'titre' => 'Tâche B']);

        $mentorA = $projetA->stage->mentor;

        $response = $this->actingAs($mentorA)->get('/tasks');

        $response->assertOk();
        $response->assertSee('Tâche A');
        $response->assertDontSee('Tâche B');
    }

    public function test_un_stagiaire_ne_peut_pas_voir_le_detail_dune_tache_dun_autre_stagiaire(): void
    {
        $projetA = $this->creerProjet();
        $projetB = $this->creerProjet();
        $tacheB = Task::create(['project_id' => $projetB->id, 'titre' => 'Tâche B']);

        $stagiaireA = $projetA->stage->stagiaire;

        $response = $this->actingAs($stagiaireA)->get("/tasks/{$tacheB->id}");

        $response->assertForbidden();
    }

    public function test_un_stagiaire_ne_peut_pas_voir_le_kanban_dun_autre_projet(): void
    {
        $projetA = $this->creerProjet();
        $projetB = $this->creerProjet();

        $stagiaireA = $projetA->stage->stagiaire;

        $response = $this->actingAs($stagiaireA)->get("/projects/{$projetB->id}/kanban");

        $response->assertForbidden();
    }

    public function test_un_stagiaire_ne_peut_pas_terminer_une_tache_meme_via_une_transition_valide(): void
    {
        $project = $this->creerProjet();
        $stagiaire = $project->stage->stagiaire;
        $task = Task::create(['project_id' => $project->id, 'titre' => 'Tâche test', 'statut' => 'en_revue']);

        $response = $this->actingAs($stagiaire)->patch("/tasks/{$task->id}/status", [
            'statut' => 'termine',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'statut' => 'en_revue']);
    }

    public function test_un_stagiaire_ne_peut_pas_acceder_au_formulaire_de_creation_de_tache(): void
    {
        $project = $this->creerProjet();
        $stagiaire = $project->stage->stagiaire;

        $response = $this->actingAs($stagiaire)->get('/tasks/create');

        $response->assertForbidden();
    }
}
