<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_depot_de_document_depuis_la_page_documents_n_existe_plus(): void
    {
        Storage::fake('local');

        foreach (['stagiaire', 'mentor', 'admin'] as $role) {
            $utilisateur = User::factory()->create(['role' => $role]);

            $this->actingAs($utilisateur)->post('/documents', [
                'titre' => 'Guide de style',
                'categorie' => 'Compte rendu',
                'fichier' => UploadedFile::fake()->create('guide.pdf', 200),
            ])->assertStatus(405);

            $this->actingAs($utilisateur)->get('/documents')->assertOk()->assertDontSee('Déposer un document');
        }

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_un_deuxieme_depot_du_meme_document_incremente_la_version(): void
    {
        Storage::fake('local');

        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $document = Document::create(['utilisateur_id' => $stagiaire->id, 'titre' => 'Guide']);
        $document->versions()->create(['numero_version' => 1, 'fichier' => 'documents/v1.pdf']);

        $service = app(DocumentService::class);
        $service->ajouterVersion($document, UploadedFile::fake()->create('v2.pdf', 200));

        $this->assertDatabaseCount('document_versions', 2);
        $this->assertDatabaseHas('document_versions', ['document_id' => $document->id, 'numero_version' => 2]);
    }

    public function test_un_ancien_document_personnel_est_visible_du_mentor_et_de_l_admin_mais_pas_des_stagiaires(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/v1.pdf', 'contenu');

        $proprietaire = User::factory()->create(['role' => 'stagiaire']);
        $document = Document::create(['utilisateur_id' => $proprietaire->id, 'titre' => 'Guide']);
        $version = $document->versions()->create(['numero_version' => 1, 'fichier' => 'documents/v1.pdf']);
        $url = "/documents/{$document->id}/versions/{$version->id}/download";

        $this->actingAs(User::factory()->create(['role' => 'mentor']))->get($url)->assertOk();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get($url)->assertOk();

        // Un stagiaire ne voit que les documents des projets qui lui sont attribués : un document sans projet
        // n'en fait pas partie, même s'il en est l'auteur.
        $this->actingAs($proprietaire)->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'stagiaire']))->get($url)->assertForbidden();
    }
}
