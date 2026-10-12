<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    protected function creerStage(User $mentor, User $stagiaire): Stage
    {
        return Stage::create([
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
        ]);
    }

    public function test_le_mentor_du_stage_peut_creer_un_projet(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $stage = $this->creerStage($mentor, $stagiaire);

        $response = $this->actingAs($mentor)->post('/projects', [
            'stage_id' => $stage->id,
            'nom' => 'Refonte du module Kanban',
            'description' => 'Migration vers un système drag-and-drop.',
        ]);

        $response->assertRedirect('/projects');
        $this->assertDatabaseHas('projects', [
            'stage_id' => $stage->id,
            'nom' => 'Refonte du module Kanban',
        ]);
    }

    public function test_un_mentor_different_ne_peut_pas_creer_un_projet_sur_ce_stage(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $autreMentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $stage = $this->creerStage($mentor, $stagiaire);

        $response = $this->actingAs($autreMentor)->post('/projects', [
            'stage_id' => $stage->id,
            'nom' => 'Tentative non autorisée',
        ]);

        $response->assertForbidden();
    }

    public function test_un_administrateur_ne_peut_pas_creer_un_projet(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $stage = $this->creerStage($mentor, $stagiaire);

        $response = $this->actingAs($admin)->post('/projects', [
            'stage_id' => $stage->id,
            'nom' => 'Tentative admin',
        ]);

        $response->assertForbidden();
    }

    public function test_on_ne_peut_pas_creer_de_projet_sur_un_stage_termine(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $stage = $this->creerStage($mentor, $stagiaire);
        $stage->update(['statut' => 'termine']);

        $response = $this->actingAs($mentor)->post('/projects', [
            'stage_id' => $stage->id,
            'nom' => 'Projet sur stage terminé',
        ]);

        $response->assertSessionHasErrors('stage_id');
    }

    public function test_un_mentor_ne_voit_que_ses_propres_projets(): void
    {
        $mentorA = User::factory()->create(['role' => 'mentor']);
        $stagiaireA = User::factory()->create(['role' => 'stagiaire']);
        $stageA = $this->creerStage($mentorA, $stagiaireA);
        Project::create(['stage_id' => $stageA->id, 'nom' => 'Projet A']);

        $mentorB = User::factory()->create(['role' => 'mentor']);
        $stagiaireB = User::factory()->create(['role' => 'stagiaire']);
        $stageB = $this->creerStage($mentorB, $stagiaireB);
        Project::create(['stage_id' => $stageB->id, 'nom' => 'Projet B']);

        $response = $this->actingAs($mentorA)->get('/projects');

        $response->assertOk();
        $response->assertSee('Projet A');
        $response->assertDontSee('Projet B');
    }

    public function test_un_stagiaire_ne_voit_que_le_projet_de_son_propre_stage(): void
    {
        $mentorA = User::factory()->create(['role' => 'mentor']);
        $stagiaireA = User::factory()->create(['role' => 'stagiaire']);
        $stageA = $this->creerStage($mentorA, $stagiaireA);
        Project::create(['stage_id' => $stageA->id, 'nom' => 'Projet A']);

        $mentorB = User::factory()->create(['role' => 'mentor']);
        $stagiaireB = User::factory()->create(['role' => 'stagiaire']);
        $stageB = $this->creerStage($mentorB, $stagiaireB);
        Project::create(['stage_id' => $stageB->id, 'nom' => 'Projet B']);

        $response = $this->actingAs($stagiaireA)->get('/projects');

        $response->assertOk();
        $response->assertSee('Projet A');
        $response->assertDontSee('Projet B');
    }

    public function test_un_mentor_peut_acceder_au_formulaire_de_creation_de_projet_depuis_le_tableau_de_bord(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $this->creerStage($mentor, $stagiaire);

        $response = $this->actingAs($mentor)->get('/projects/create');

        $response->assertOk();
    }

    public function test_un_stagiaire_ne_peut_pas_acceder_au_formulaire_de_creation_de_projet(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $this->creerStage($mentor, $stagiaire);

        $response = $this->actingAs($stagiaire)->get('/projects/create');

        $response->assertForbidden();
    }

    public function test_un_administrateur_ne_peut_pas_acceder_au_formulaire_de_creation_de_projet(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $stage = $this->creerStage($mentor, $stagiaire);

        $this->actingAs($admin)->get('/projects/create')->assertForbidden();
        $this->actingAs($admin)->get("/stages/{$stage->id}/projects/create")->assertForbidden();
    }

    public function test_le_mentor_du_stage_peut_modifier_son_projet(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $stage = $this->creerStage($mentor, $stagiaire);
        $project = Project::create(['stage_id' => $stage->id, 'nom' => 'Ancien nom']);

        $response = $this->actingAs($mentor)->put("/projects/{$project->id}", [
            'nom' => 'Nouveau nom',
            'description' => 'Nouvelle description',
        ]);

        $response->assertRedirect('/projects');
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'nom' => 'Nouveau nom']);
    }

    public function test_un_autre_mentor_ne_peut_pas_modifier_le_projet(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $autreMentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $stage = $this->creerStage($mentor, $stagiaire);
        $project = Project::create(['stage_id' => $stage->id, 'nom' => 'Projet']);

        $response = $this->actingAs($autreMentor)->put("/projects/{$project->id}", [
            'nom' => 'Tentative',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'nom' => 'Projet']);
    }

    public function test_le_mentor_du_stage_peut_supprimer_son_projet(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $stage = $this->creerStage($mentor, $stagiaire);
        $project = Project::create(['stage_id' => $stage->id, 'nom' => 'Projet']);

        $response = $this->actingAs($mentor)->delete("/projects/{$project->id}");

        $response->assertRedirect('/projects');
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_la_suppression_dun_projet_supprime_ses_taches_en_cascade(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $stage = $this->creerStage($mentor, $stagiaire);
        $project = Project::create(['stage_id' => $stage->id, 'nom' => 'Projet']);
        $tache1 = \App\Models\Task::create(['project_id' => $project->id, 'titre' => 'Tâche 1']);
        $tache2 = \App\Models\Task::create(['project_id' => $project->id, 'titre' => 'Tâche 2']);

        $this->actingAs($mentor)->delete("/projects/{$project->id}");

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertDatabaseMissing('tasks', ['id' => $tache1->id]);
        $this->assertDatabaseMissing('tasks', ['id' => $tache2->id]);
    }

    public function test_un_administrateur_ne_peut_pas_supprimer_un_projet(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $stage = $this->creerStage($mentor, $stagiaire);
        $project = Project::create(['stage_id' => $stage->id, 'nom' => 'Projet']);

        $response = $this->actingAs($admin)->delete("/projects/{$project->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    public function test_un_stagiaire_ne_peut_pas_supprimer_un_projet(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $stage = $this->creerStage($mentor, $stagiaire);
        $project = Project::create(['stage_id' => $stage->id, 'nom' => 'Projet']);

        $response = $this->actingAs($stagiaire)->delete("/projects/{$project->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }
}
