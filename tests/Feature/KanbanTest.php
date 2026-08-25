<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KanbanTest extends TestCase
{
    use RefreshDatabase;

    protected function creerTache(string $statut = 'a_faire'): array
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        $stage = Stage::create([
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
        ]);

        $project = Project::create(['stage_id' => $stage->id, 'nom' => 'Projet test']);

        $task = Task::create(['project_id' => $project->id, 'titre' => 'Tâche test', 'statut' => $statut]);

        return compact('mentor', 'stagiaire', 'task');
    }

    public function test_le_mentor_peut_valider_une_tache_comme_terminee(): void
    {
        ['mentor' => $mentor, 'task' => $task] = $this->creerTache('en_revue');

        $response = $this->actingAs($mentor)->patchJson("/tasks/{$task->id}/status", [
            'statut' => 'termine',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'statut' => 'termine']);
    }

    public function test_le_stagiaire_ne_peut_pas_valider_une_tache_comme_terminee(): void
    {
        ['stagiaire' => $stagiaire, 'task' => $task] = $this->creerTache('en_revue');

        $response = $this->actingAs($stagiaire)->patchJson("/tasks/{$task->id}/status", [
            'statut' => 'termine',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'statut' => 'en_revue']);
    }

    public function test_le_stagiaire_peut_faire_progresser_une_tache_vers_un_statut_intermediaire(): void
    {
        ['stagiaire' => $stagiaire, 'task' => $task] = $this->creerTache('a_faire');

        $response = $this->actingAs($stagiaire)->patchJson("/tasks/{$task->id}/status", [
            'statut' => 'en_cours',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'statut' => 'en_cours']);
    }

    public function test_une_tache_deja_terminee_ne_peut_plus_changer_de_statut(): void
    {
        ['mentor' => $mentor, 'task' => $task] = $this->creerTache('termine');

        $response = $this->actingAs($mentor)->patchJson("/tasks/{$task->id}/status", [
            'statut' => 'en_revue',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'statut' => 'termine']);
    }

    public function test_la_vue_kanban_affiche_les_taches_groupees_par_statut(): void
    {
        ['mentor' => $mentor, 'task' => $task] = $this->creerTache('en_cours');

        $response = $this->actingAs($mentor)->get("/projects/{$task->project_id}/kanban");

        $response->assertOk();
        $response->assertViewHas('colonnes');
        $response->assertSee('Tâche test');
    }
}
