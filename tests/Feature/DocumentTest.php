<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_utilisateur_peut_deposer_un_document(): void
    {
        Storage::fake('local');

        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $fichier = UploadedFile::fake()->create('guide.pdf', 200);

        $response = $this->actingAs($stagiaire)->post('/documents', [
            'titre' => 'Guide de style',
            'categorie' => 'Technique',
            'fichier' => $fichier,
        ]);

        $response->assertRedirect('/documents');
        $this->assertDatabaseHas('documents', ['titre' => 'Guide de style']);
        $this->assertDatabaseHas('document_versions', ['numero_version' => 1]);
    }

    public function test_un_deuxieme_depot_du_meme_document_incremente_la_version(): void
    {
        Storage::fake('local');

        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $document = Document::create(['utilisateur_id' => $stagiaire->id, 'titre' => 'Guide']);
        $document->versions()->create(['numero_version' => 1, 'fichier' => 'documents/v1.pdf']);

        $service = app(\App\Services\DocumentService::class);
        $service->ajouterVersion($document, UploadedFile::fake()->create('v2.pdf', 200));

        $this->assertDatabaseCount('document_versions', 2);
        $this->assertDatabaseHas('document_versions', ['document_id' => $document->id, 'numero_version' => 2]);
    }

    public function test_un_autre_utilisateur_ne_peut_pas_voir_le_document(): void
    {
        Storage::fake('local');

        $proprietaire = User::factory()->create(['role' => 'stagiaire']);
        $autreUtilisateur = User::factory()->create(['role' => 'mentor']);

        $document = Document::create(['utilisateur_id' => $proprietaire->id, 'titre' => 'Guide']);
        $version = $document->versions()->create(['numero_version' => 1, 'fichier' => 'documents/v1.pdf']);

        $response = $this->actingAs($autreUtilisateur)->get("/documents/{$document->id}/versions/{$version->id}/download");

        $response->assertForbidden();
    }
}
