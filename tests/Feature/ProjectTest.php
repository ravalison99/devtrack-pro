<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_un_mentor_peut_creer_un_projet_avec_son_cahier_des_charges(): void
    {
        Storage::fake('local');
        $mentor = User::factory()->create(['role' => 'mentor']);

        $response = $this->actingAs($mentor)->post('/projects', [
            'nom' => 'Refonte du module Kanban',
            'description' => 'Migration vers un système drag-and-drop.',
            'cahier_charges' => UploadedFile::fake()->create('cahier.pdf', 200, 'application/pdf'),
        ]);

        $response->assertRedirect('/projects');
        $projet = Project::where('nom', 'Refonte du module Kanban')->firstOrFail();
        $this->assertSame($mentor->id, $projet->mentor_id);
        $this->assertNull($projet->stage_id);
        $this->assertSame('cahier.pdf', $projet->cahier_charges_nom);
        Storage::disk('local')->assertExists($projet->cahier_charges_chemin);
        // La création d'un projet ne crée ni n'affecte aucun groupe.
        $this->assertDatabaseCount('groupes', 0);
    }

    public function test_le_nom_et_le_cahier_des_charges_sont_obligatoires(): void
    {
        Storage::fake('local');
        $mentor = User::factory()->create(['role' => 'mentor']);

        $this->actingAs($mentor)->post('/projects', [])->assertSessionHasErrors(['nom', 'cahier_charges']);
        $this->assertDatabaseCount('projects', 0);
    }

    public function test_le_type_et_la_taille_du_cahier_des_charges_sont_controles(): void
    {
        Storage::fake('local');
        $mentor = User::factory()->create(['role' => 'mentor']);

        $this->actingAs($mentor)->post('/projects', [
            'nom' => 'P',
            'cahier_charges' => UploadedFile::fake()->create('script.php', 10, 'text/x-php'),
        ])->assertSessionHasErrors('cahier_charges');

        $this->actingAs($mentor)->post('/projects', [
            'nom' => 'P',
            'cahier_charges' => UploadedFile::fake()->create('gros.pdf', 6000, 'application/pdf'),
        ])->assertSessionHasErrors('cahier_charges');

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_un_stagiaire_et_un_administrateur_ne_peuvent_pas_creer_de_projet(): void
    {
        Storage::fake('local');

        foreach (['stagiaire', 'admin'] as $role) {
            $utilisateur = User::factory()->create(['role' => $role]);

            $this->actingAs($utilisateur)->post('/projects', [
                'nom' => 'Tentative',
                'cahier_charges' => UploadedFile::fake()->create('cahier.pdf', 100, 'application/pdf'),
            ])->assertForbidden();
        }

        $this->assertDatabaseCount('projects', 0);
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
    }
}
