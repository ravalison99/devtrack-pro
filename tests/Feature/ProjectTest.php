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
}
