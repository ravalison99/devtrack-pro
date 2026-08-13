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
}
