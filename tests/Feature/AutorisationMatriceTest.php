<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vérifie qu'un accès direct par identifiant (manipulation d'URL) reste bloqué
 * pour un utilisateur hors du périmètre de la ressource, quel que soit son rôle.
 */
class AutorisationMatriceTest extends TestCase
{
    use RefreshDatabase;

    protected function creerProjetAvecTache(): array
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        $stage = Stage::create([
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
        ]);

        $project = Project::create(['stage_id' => $stage->id, 'nom' => 'Projet']);
        $task = Task::create(['project_id' => $project->id, 'titre' => 'Tâche']);

        return compact('mentor', 'stagiaire', 'stage', 'project', 'task');
    }

    public function test_un_visiteur_non_authentifie_est_redirige_vers_la_connexion(): void
    {
        ['task' => $task, 'project' => $project] = $this->creerProjetAvecTache();

        $this->get('/tasks')->assertRedirect('/login');
        $this->get("/tasks/{$task->id}")->assertRedirect('/login');
        $this->get("/projects/{$project->id}/kanban")->assertRedirect('/login');
        $this->get('/stages')->assertRedirect('/login');
        $this->get('/users')->assertRedirect('/login');
    }

    public function test_un_stagiaire_hors_perimetre_ne_peut_pas_acceder_a_une_tache_par_id(): void
    {
        $ressourceA = $this->creerProjetAvecTache();
        $ressourceB = $this->creerProjetAvecTache();

        $response = $this->actingAs($ressourceA['stagiaire'])->get("/tasks/{$ressourceB['task']->id}");

        $response->assertForbidden();
    }

    public function test_un_mentor_hors_perimetre_ne_peut_pas_acceder_au_kanban_dun_autre_projet(): void
    {
        $ressourceA = $this->creerProjetAvecTache();
        $ressourceB = $this->creerProjetAvecTache();

        $response = $this->actingAs($ressourceA['mentor'])->get("/projects/{$ressourceB['project']->id}/kanban");

        $response->assertForbidden();
    }

    public function test_un_mentor_hors_perimetre_ne_peut_pas_changer_le_statut_dune_tache_qui_ne_lui_appartient_pas(): void
    {
        $ressourceA = $this->creerProjetAvecTache();
        $ressourceB = $this->creerProjetAvecTache();

        $response = $this->actingAs($ressourceA['mentor'])->patch("/tasks/{$ressourceB['task']->id}/status", [
            'statut' => 'en_cours',
        ]);

        $response->assertForbidden();
    }

    public function test_un_stagiaire_ne_peut_pas_acceder_aux_pages_reservees_a_ladministrateur(): void
    {
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        $this->actingAs($stagiaire)->get('/users')->assertForbidden();
        $this->actingAs($stagiaire)->get('/stages/create')->assertForbidden();
        $this->actingAs($stagiaire)->post('/users', [
            'name' => 'Intrus',
            'email' => 'intrus@example.com',
            'password' => 'motdepasse123',
            'role' => 'admin',
        ])->assertForbidden();
    }

    public function test_un_mentor_ne_peut_pas_acceder_aux_pages_reservees_a_ladministrateur(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);

        $this->actingAs($mentor)->get('/users')->assertForbidden();
        $this->actingAs($mentor)->get('/stages/create')->assertForbidden();
    }
}
