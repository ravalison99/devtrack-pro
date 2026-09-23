<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Project;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectDocumentTest extends TestCase
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

        return Project::create(['stage_id' => $stage->id, 'nom' => 'Projet de test']);
    }

    public function test_le_mentor_du_projet_peut_deposer_un_document_sur_son_projet(): void
    {
        Storage::fake('local');

        $project = $this->creerProjet();
        $mentor = $project->stage->mentor;
        $fichier = UploadedFile::fake()->create('cahier-des-charges.pdf', 300);

        $response = $this->actingAs($mentor)->post('/documents', [
            'titre' => 'Cahier des charges',
            'fichier' => $fichier,
            'project_id' => $project->id,
        ]);

        $response->assertRedirect("/projects/{$project->id}/kanban");
        $this->assertDatabaseHas('documents', [
            'titre' => 'Cahier des charges',
            'project_id' => $project->id,
            'utilisateur_id' => $mentor->id,
        ]);
        $this->assertDatabaseHas('document_versions', ['numero_version' => 1]);
    }

    public function test_le_stagiaire_du_projet_peut_voir_et_telecharger_le_document(): void
    {
        Storage::fake('local');

        $project = $this->creerProjet();
        $mentor = $project->stage->mentor;
        $stagiaire = $project->stage->stagiaire;

        $document = Document::create([
            'utilisateur_id' => $mentor->id,
            'project_id' => $project->id,
            'titre' => 'Cahier des charges',
        ]);
        $version = $document->versions()->create(['numero_version' => 1, 'fichier' => 'documents/v1.pdf']);
        Storage::disk('local')->put('documents/v1.pdf', 'contenu de test');

        $listeResponse = $this->actingAs($stagiaire)->get("/projects/{$project->id}/documents");
        $listeResponse->assertOk();
        $listeResponse->assertSee('Cahier des charges');

        $telechargementResponse = $this->actingAs($stagiaire)->get("/documents/{$document->id}/versions/{$version->id}/download");
        $telechargementResponse->assertOk();
    }

    public function test_ladministrateur_peut_voir_et_telecharger_le_document_du_projet(): void
    {
        Storage::fake('local');

        $project = $this->creerProjet();
        $mentor = $project->stage->mentor;
        $admin = User::factory()->create(['role' => 'admin']);

        $document = Document::create([
            'utilisateur_id' => $mentor->id,
            'project_id' => $project->id,
            'titre' => 'Cahier des charges',
        ]);
        $version = $document->versions()->create(['numero_version' => 1, 'fichier' => 'documents/v1.pdf']);
        Storage::disk('local')->put('documents/v1.pdf', 'contenu de test');

        $this->actingAs($admin)->get("/projects/{$project->id}/documents")->assertOk();
        $this->actingAs($admin)->get("/documents/{$document->id}/versions/{$version->id}/download")->assertOk();
    }

    public function test_un_stagiaire_dun_autre_projet_ne_peut_pas_acceder_au_document(): void
    {
        Storage::fake('local');

        $projetA = $this->creerProjet();
        $projetB = $this->creerProjet();
        $mentorA = $projetA->stage->mentor;
        $stagiaireB = $projetB->stage->stagiaire;

        $document = Document::create([
            'utilisateur_id' => $mentorA->id,
            'project_id' => $projetA->id,
            'titre' => 'Cahier des charges',
        ]);
        $version = $document->versions()->create(['numero_version' => 1, 'fichier' => 'documents/v1.pdf']);

        $this->actingAs($stagiaireB)->get("/projects/{$projetA->id}/documents")->assertForbidden();
        $this->actingAs($stagiaireB)->get("/documents/{$document->id}/versions/{$version->id}/download")->assertForbidden();
    }

    public function test_un_mentor_dun_autre_projet_ne_peut_pas_deposer_de_document_sur_ce_projet(): void
    {
        Storage::fake('local');

        $project = $this->creerProjet();
        $autreMentor = User::factory()->create(['role' => 'mentor']);
        $fichier = UploadedFile::fake()->create('intrusion.pdf', 200);

        $response = $this->actingAs($autreMentor)->post('/documents', [
            'titre' => 'Intrusion',
            'fichier' => $fichier,
            'project_id' => $project->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('documents', ['titre' => 'Intrusion']);
    }

    public function test_un_document_personnel_sans_projet_reste_invisible_au_mentor(): void
    {
        Storage::fake('local');

        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $mentor = User::factory()->create(['role' => 'mentor']);

        $document = Document::create(['utilisateur_id' => $stagiaire->id, 'titre' => 'Document personnel']);
        $version = $document->versions()->create(['numero_version' => 1, 'fichier' => 'documents/v1.pdf']);

        $response = $this->actingAs($mentor)->get("/documents/{$document->id}/versions/{$version->id}/download");

        $response->assertForbidden();
    }

    public function test_le_depot_personnel_sans_projet_fonctionne_toujours_exactement_comme_avant(): void
    {
        Storage::fake('local');

        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $fichier = UploadedFile::fake()->create('guide.pdf', 200);

        $response = $this->actingAs($stagiaire)->post('/documents', [
            'titre' => 'Guide personnel',
            'categorie' => 'Compte rendu',
            'fichier' => $fichier,
        ]);

        $response->assertRedirect('/documents');
        $this->assertDatabaseHas('documents', [
            'titre' => 'Guide personnel',
            'utilisateur_id' => $stagiaire->id,
            'project_id' => null,
        ]);
    }
}
