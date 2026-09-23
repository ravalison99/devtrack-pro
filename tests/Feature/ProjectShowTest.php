<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectShowTest extends TestCase
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

    public function test_le_mentor_peut_creer_un_projet_avec_un_document_en_une_seule_soumission(): void
    {
        Storage::fake('local');

        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $stage = $this->creerStage($mentor, $stagiaire);
        $fichier = UploadedFile::fake()->create('cahier-des-charges.pdf', 300);

        $response = $this->actingAs($mentor)->post('/projects', [
            'stage_id' => $stage->id,
            'nom' => 'Portail RH',
            'description' => 'Développement du portail RH.',
            'document_titre' => 'Cahier des charges',
            'fichier' => $fichier,
        ]);

        $response->assertRedirect('/projects');
        $project = Project::where('nom', 'Portail RH')->firstOrFail();

        $this->assertDatabaseHas('documents', [
            'titre' => 'Cahier des charges',
            'project_id' => $project->id,
            'utilisateur_id' => $mentor->id,
        ]);
        $this->assertDatabaseHas('document_versions', ['numero_version' => 1]);
    }

    public function test_un_stagiaire_ne_voit_dans_projects_que_les_projets_de_son_propre_stage(): void
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

    public function test_le_mentor_peut_creer_trois_taches_dun_coup_via_storebatch(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $stage = $this->creerStage($mentor, $stagiaire);
        $project = Project::create(['stage_id' => $stage->id, 'nom' => 'Portail RH']);

        $response = $this->actingAs($mentor)->post("/projects/{$project->id}/tasks/batch", [
            'taches' => [
                ['titre' => 'Analyse des besoins', 'priorite' => 'haute'],
                ['titre' => 'Maquettes', 'priorite' => 'moyenne'],
                ['titre' => 'Mise en place du dépôt', 'priorite' => 'basse'],
            ],
        ]);

        $response->assertRedirect("/projects/{$project->id}/details");
        $this->assertDatabaseCount('tasks', 3);
        $this->assertDatabaseHas('tasks', ['project_id' => $project->id, 'titre' => 'Analyse des besoins', 'priorite' => 'haute']);
        $this->assertDatabaseHas('tasks', ['project_id' => $project->id, 'titre' => 'Maquettes', 'priorite' => 'moyenne']);
        $this->assertDatabaseHas('tasks', ['project_id' => $project->id, 'titre' => 'Mise en place du dépôt', 'priorite' => 'basse']);
    }

    public function test_un_stagiaire_dun_autre_projet_ne_peut_pas_acceder_a_projects_show(): void
    {
        $mentorA = User::factory()->create(['role' => 'mentor']);
        $stagiaireA = User::factory()->create(['role' => 'stagiaire']);
        $stageA = $this->creerStage($mentorA, $stagiaireA);
        $projectA = Project::create(['stage_id' => $stageA->id, 'nom' => 'Projet A']);

        $mentorB = User::factory()->create(['role' => 'mentor']);
        $stagiaireB = User::factory()->create(['role' => 'stagiaire']);
        $this->creerStage($mentorB, $stagiaireB);

        $response = $this->actingAs($stagiaireB)->get("/projects/{$projectA->id}/details");

        $response->assertForbidden();
    }

    public function test_un_stagiaire_peut_telecharger_un_document_rattache_a_son_propre_projet(): void
    {
        Storage::fake('local');

        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $stage = $this->creerStage($mentor, $stagiaire);
        $project = Project::create(['stage_id' => $stage->id, 'nom' => 'Portail RH']);

        $document = \App\Models\Document::create([
            'utilisateur_id' => $mentor->id,
            'project_id' => $project->id,
            'titre' => 'Cahier des charges',
        ]);
        $version = $document->versions()->create(['numero_version' => 1, 'fichier' => 'documents/v1.pdf']);
        Storage::disk('local')->put('documents/v1.pdf', 'contenu de test');

        $response = $this->actingAs($stagiaire)->get("/documents/{$document->id}/versions/{$version->id}/download");

        $response->assertOk();
    }
}
