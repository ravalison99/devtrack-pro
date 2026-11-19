<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_administrateur_voit_le_nombre_de_stages_actifs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        Stage::create([
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
            'statut' => 'en_cours',
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('1');
        $response->assertSee('stage');
    }

    public function test_un_stagiaire_sans_aucune_tache_voit_des_zeros_partout(): void
    {
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        $response = $this->actingAs($stagiaire)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('À faire');
        $response->assertSee('Terminées');
    }

    public function test_un_stagiaire_voit_ses_taches_reparties_par_statut(): void
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

        Task::create(['project_id' => $project->id, 'titre' => 'Tâche 1', 'statut' => 'a_faire']);
        Task::create(['project_id' => $project->id, 'titre' => 'Tâche 2', 'statut' => 'a_faire']);
        Task::create(['project_id' => $project->id, 'titre' => 'Tâche 3', 'statut' => 'termine']);

        $response = $this->actingAs($stagiaire)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('À faire');
    }
}
