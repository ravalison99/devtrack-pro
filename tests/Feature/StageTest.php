<?php

namespace Tests\Feature;

use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StageTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_administrateur_peut_creer_un_stage(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        $response = $this->actingAs($admin)->post('/stages', [
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-09-01',
            'date_fin' => '2026-11-01',
            'mode_travail' => 'presentiel',
        ]);

        $response->assertRedirect('/stages');
        $this->assertDatabaseHas('stages', [
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
        ]);
    }

    public function test_un_stagiaire_deja_affecte_ne_peut_pas_recevoir_un_second_stage_actif(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        Stage::create([
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
        ]);

        $response = $this->actingAs($admin)->post('/stages', [
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-09-01',
            'date_fin' => '2026-11-01',
            'mode_travail' => 'presentiel',
        ]);

        $response->assertSessionHasErrors('stagiaire_id');
    }

    public function test_un_mentor_ne_peut_pas_creer_un_stage(): void
    {
        $mentorConnecte = User::factory()->create(['role' => 'mentor']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        $response = $this->actingAs($mentorConnecte)->post('/stages', [
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-09-01',
            'date_fin' => '2026-11-01',
            'mode_travail' => 'presentiel',
        ]);

        $response->assertForbidden();
    }

    public function test_un_mentor_ne_voit_que_ses_propres_stages(): void
    {
        $mentorA = User::factory()->create(['role' => 'mentor']);
        $stagiaireA = User::factory()->create(['role' => 'stagiaire', 'name' => 'Stagiaire A']);
        Stage::create([
            'stagiaire_id' => $stagiaireA->id,
            'mentor_id' => $mentorA->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
        ]);

        $mentorB = User::factory()->create(['role' => 'mentor']);
        $stagiaireB = User::factory()->create(['role' => 'stagiaire', 'name' => 'Stagiaire B']);
        Stage::create([
            'stagiaire_id' => $stagiaireB->id,
            'mentor_id' => $mentorB->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
        ]);

        $response = $this->actingAs($mentorA)->get('/stages');

        $response->assertOk();
        $response->assertSee('Stagiaire A');
        $response->assertDontSee('Stagiaire B');
    }

    public function test_un_mentor_peut_filtrer_ses_stages_par_statut(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiairePlanifie = User::factory()->create(['role' => 'stagiaire', 'name' => 'Planifié']);
        Stage::create([
            'stagiaire_id' => $stagiairePlanifie->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
        ]);

        $stagiaireEnCours = User::factory()->create(['role' => 'stagiaire', 'name' => 'En Cours']);
        Stage::create([
            'stagiaire_id' => $stagiaireEnCours->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
            'statut' => 'en_cours',
        ]);

        $response = $this->actingAs($mentor)->get('/stages?statut=en_cours');

        $response->assertOk();
        $response->assertSee('En Cours');
        $response->assertDontSee('Planifié');
    }

    public function test_un_admin_peut_faire_passer_un_stage_de_planifie_a_en_cours(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $stage = Stage::create([
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
        ]);

        $response = $this->actingAs($admin)->patch("/stages/{$stage->id}/status", ['statut' => 'en_cours']);

        $response->assertRedirect();
        $this->assertDatabaseHas('stages', ['id' => $stage->id, 'statut' => 'en_cours']);
    }

    public function test_une_transition_de_statut_de_stage_invalide_est_refusee(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $stage = Stage::create([
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
        ]);

        $response = $this->actingAs($admin)->patch("/stages/{$stage->id}/status", ['statut' => 'termine']);

        $response->assertSessionHasErrors('statut');
        $this->assertDatabaseHas('stages', ['id' => $stage->id, 'statut' => 'planifie']);
    }

    public function test_un_mentor_ne_peut_pas_changer_le_statut_dun_stage(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $stage = Stage::create([
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
        ]);

        $response = $this->actingAs($mentor)->patch("/stages/{$stage->id}/status", ['statut' => 'en_cours']);

        $response->assertForbidden();
    }

    public function test_un_stage_actif_ne_peut_pas_etre_supprime(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $stage = Stage::create([
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
        ]);

        $response = $this->actingAs($admin)->delete("/stages/{$stage->id}");

        $response->assertSessionHasErrors('statut');
        $this->assertDatabaseHas('stages', ['id' => $stage->id]);
        $this->assertNull($stage->fresh()->deleted_at);
    }

    public function test_un_stage_termine_peut_etre_supprime_sans_toucher_aux_projets_et_taches(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $stage = Stage::create([
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
            'statut' => 'termine',
        ]);
        $project = \App\Models\Project::create(['stage_id' => $stage->id, 'nom' => 'Projet historique']);
        $task = \App\Models\Task::create(['project_id' => $project->id, 'titre' => 'Tâche historique']);

        $response = $this->actingAs($admin)->delete("/stages/{$stage->id}");

        $response->assertRedirect('/stages');
        $this->assertSoftDeleted('stages', ['id' => $stage->id]);
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    public function test_un_mentor_ne_peut_pas_supprimer_un_stage(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $stage = Stage::create([
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
            'statut' => 'termine',
        ]);

        $response = $this->actingAs($mentor)->delete("/stages/{$stage->id}");

        $response->assertForbidden();
    }

    public function test_le_mentor_voit_lemail_du_stagiaire_dans_la_liste_des_stages(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire', 'email' => 'stagiaire.visible@example.com']);
        Stage::create([
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
        ]);

        $response = $this->actingAs($mentor)->get('/stages');

        $response->assertOk();
        $response->assertSee('stagiaire.visible@example.com');
    }

    public function test_le_bloc_stagiaires_actifs_est_scope_au_mentor(): void
    {
        $mentorA = User::factory()->create(['role' => 'mentor']);
        $stagiaireActifA = User::factory()->create(['role' => 'stagiaire', 'name' => 'Actif A']);
        Stage::create([
            'stagiaire_id' => $stagiaireActifA->id,
            'mentor_id' => $mentorA->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
            'statut' => 'en_cours',
        ]);

        $mentorB = User::factory()->create(['role' => 'mentor']);
        $stagiaireActifB = User::factory()->create(['role' => 'stagiaire', 'name' => 'Actif B']);
        Stage::create([
            'stagiaire_id' => $stagiaireActifB->id,
            'mentor_id' => $mentorB->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
            'statut' => 'en_cours',
        ]);

        $response = $this->actingAs($mentorA)->get('/stages');

        $response->assertOk();
        $response->assertSee('Actif A');
        $response->assertDontSee('Actif B');
    }

    public function test_un_stagiaire_ne_voit_pas_le_bloc_stagiaires_actifs(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        Stage::create([
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
            'statut' => 'en_cours',
        ]);

        $response = $this->actingAs($stagiaire)->get('/stages');

        $response->assertOk();
        $response->assertDontSee('Stagiaires actifs');
    }
}
