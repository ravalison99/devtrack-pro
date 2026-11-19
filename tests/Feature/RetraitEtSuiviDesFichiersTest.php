<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Groupe;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RetraitEtSuiviDesFichiersTest extends TestCase
{
    use RefreshDatabase;

    protected User $mentor;

    protected User $chef;

    protected User $membre;

    protected Project $projet;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->mentor = User::factory()->create(['role' => 'mentor', 'name' => 'Mentor Martin']);
        $this->chef = User::factory()->create(['role' => 'stagiaire', 'name' => 'Chef Camille']);
        $this->membre = User::factory()->create(['role' => 'stagiaire', 'name' => 'Membre Michel']);

        $this->projet = Project::create(['mentor_id' => $this->mentor->id, 'nom' => 'Projet Alpha']);
        Groupe::create(['nom' => 'Groupe Alpha', 'mentor_id' => $this->mentor->id, 'chef_id' => $this->chef->id, 'project_id' => $this->projet->id])
            ->membres()->attach([$this->chef->id, $this->membre->id]);
    }

    protected function depot(string $titre = 'Rapport 1'): void
    {
        $this->actingAs($this->chef)->post("/projects/{$this->projet->id}/documents", [
            'titre' => $titre,
            'categorie' => 'Compte rendu',
            'type_rapport' => 'hebdomadaire',
            'fichier' => UploadedFile::fake()->create('rapport.pdf', 100, 'application/pdf'),
        ])->assertRedirect();
    }

    protected function urlRetrait(Document $document, DocumentVersion $version): string
    {
        return "/documents/{$document->id}/versions/{$version->id}";
    }

    // ---- Retrait par le chef ---------------------------------------------------------

    public function test_le_chef_retire_une_version_et_la_precedente_redevient_la_version_actuelle(): void
    {
        $this->depot();
        $this->depot();
        $document = Document::firstOrFail();
        [$v1, $v2] = $document->versions()->orderBy('numero_version')->get()->all();

        $this->actingAs($this->chef)->delete($this->urlRetrait($document, $v2))
            ->assertRedirect("/projects/{$this->projet->id}");

        $this->assertDatabaseMissing('document_versions', ['id' => $v2->id]);
        Storage::disk('local')->assertMissing($v2->fichier);
        // L'autre version est intacte, fichier compris.
        $this->assertDatabaseHas('document_versions', ['id' => $v1->id]);
        Storage::disk('local')->assertExists($v1->fichier);
        $this->assertSame($v1->id, $document->fresh()->derniereVersion()->id);
    }

    public function test_retirer_la_derniere_version_supprime_aussi_le_document(): void
    {
        $this->depot();
        $document = Document::firstOrFail();
        $version = $document->versions()->firstOrFail();

        $this->actingAs($this->chef)->delete($this->urlRetrait($document, $version))->assertRedirect();

        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_versions', 0);
        Storage::disk('local')->assertMissing($version->fichier);
        $this->actingAs($this->chef)->get("/projects/{$this->projet->id}")->assertSee('Aucun rapport soumis');
    }

    public function test_un_nouveau_depot_apres_retrait_repart_sur_le_meme_document(): void
    {
        $this->depot();
        $this->depot();
        $document = Document::firstOrFail();
        $this->actingAs($this->chef)->delete($this->urlRetrait($document, $document->derniereVersion()));

        $this->depot();

        $this->assertDatabaseCount('documents', 1);
        $this->assertSame(2, $document->fresh()->versions()->count());
    }

    public function test_seul_le_chef_deposant_peut_retirer_le_fichier(): void
    {
        $this->depot();
        $document = Document::firstOrFail();
        $version = $document->versions()->firstOrFail();

        $autreChef = User::factory()->create(['role' => 'stagiaire']);
        $autre = Project::create(['mentor_id' => $this->mentor->id, 'nom' => 'Projet Beta']);
        Groupe::create(['nom' => 'Groupe Beta', 'mentor_id' => $this->mentor->id, 'chef_id' => $autreChef->id, 'project_id' => $autre->id])
            ->membres()->attach($autreChef->id);

        $interdits = [
            $this->mentor,
            User::factory()->create(['role' => 'admin']),
            $this->membre,
            $autreChef,
            User::factory()->create(['role' => 'mentor']),
        ];

        foreach ($interdits as $utilisateur) {
            $this->actingAs($utilisateur)->delete($this->urlRetrait($document, $version))->assertForbidden();
        }

        $this->assertDatabaseHas('document_versions', ['id' => $version->id]);
        Storage::disk('local')->assertExists($version->fichier);

        auth()->logout();
        $this->delete($this->urlRetrait($document, $version))->assertRedirect('/login');
    }

    public function test_une_version_qui_n_appartient_pas_au_document_est_introuvable(): void
    {
        $this->depot('Rapport 1');
        $this->depot('Rapport 2');
        [$d1, $d2] = Document::orderBy('id')->get()->all();

        $this->actingAs($this->chef)->delete($this->urlRetrait($d1, $d2->versions()->firstOrFail()))->assertNotFound();
        $this->assertDatabaseCount('document_versions', 2);
    }

    public function test_les_documents_personnels_ne_sont_pas_concernes(): void
    {
        $document = Document::create(['utilisateur_id' => $this->chef->id, 'titre' => 'Perso']);
        $version = $document->versions()->create(['numero_version' => 1, 'fichier' => 'documents/perso.pdf', 'utilisateur_id' => $this->chef->id]);

        $this->actingAs($this->chef)->delete($this->urlRetrait($document, $version))->assertForbidden();
        $this->assertDatabaseHas('document_versions', ['id' => $version->id]);
    }

    public function test_le_bouton_retirer_n_apparait_que_pour_le_chef_deposant(): void
    {
        $this->depot();

        $this->actingAs($this->chef)->get("/projects/{$this->projet->id}")->assertSee('Retirer');
        $this->actingAs($this->mentor)->get("/projects/{$this->projet->id}")->assertDontSee('Retirer');
        $this->actingAs($this->membre)->get("/projects/{$this->projet->id}")->assertDontSee('Retirer');
    }

    // ---- Suivi par le mentor ---------------------------------------------------------

    public function test_le_mentor_voit_et_telecharge_les_fichiers_des_chefs_de_ses_projets(): void
    {
        $this->depot('Rapport du chef');
        $document = Document::firstOrFail();
        $version = $document->versions()->firstOrFail();

        $this->actingAs($this->mentor)->get('/documents')
            ->assertOk()
            ->assertSee('Rapport du chef')
            ->assertSee('Projet Alpha')
            ->assertSee('Groupe Alpha')
            ->assertSee('Chef Camille')
            ->assertSee('Télécharger');

        $this->actingAs($this->mentor)->get("/documents/{$document->id}/versions/{$version->id}/download")->assertOk();
        $this->actingAs($this->mentor)->get("/projects/{$this->projet->id}")->assertSee('Rapport du chef');
    }

    public function test_tout_mentor_voit_et_telecharge_les_fichiers_de_tous_les_projets(): void
    {
        $this->depot('Rapport du chef');
        $document = Document::firstOrFail();
        $version = $document->versions()->firstOrFail();
        $autreMentor = User::factory()->create(['role' => 'mentor']);

        $this->actingAs($autreMentor)->get('/documents')->assertSee('Rapport du chef');
        $this->actingAs($autreMentor)->get("/documents/{$document->id}/versions/{$version->id}/download")->assertOk();
    }

    public function test_un_membre_voit_dans_la_page_documents_les_fichiers_de_son_projet(): void
    {
        $this->depot('Rapport du chef');

        $this->actingAs($this->membre)->get('/documents')->assertOk()->assertSee('Rapport du chef');
    }
}
