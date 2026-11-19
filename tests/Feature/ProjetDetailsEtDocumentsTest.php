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

class ProjetDetailsEtDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected User $mentor;

    protected User $chef;

    protected User $membre;

    protected Project $projet;

    protected Groupe $groupe;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->mentor = User::factory()->create(['role' => 'mentor', 'name' => 'Mentor Martin']);
        $this->chef = User::factory()->create(['role' => 'stagiaire', 'name' => 'Chef Camille']);
        $this->membre = User::factory()->create(['role' => 'stagiaire', 'name' => 'Membre Michel']);

        $this->projet = $this->creerProjet('Projet Alpha');
        $this->groupe = Groupe::create([
            'nom' => 'Groupe Alpha',
            'mentor_id' => $this->mentor->id,
            'chef_id' => $this->chef->id,
            'project_id' => $this->projet->id,
        ]);
        $this->groupe->membres()->attach([$this->chef->id, $this->membre->id]);
    }

    protected function creerProjet(string $nom, ?User $mentor = null): Project
    {
        $chemin = UploadedFile::fake()->create('cahier.pdf', 100, 'application/pdf')->store('projets/cahiers-des-charges', 'local');

        return Project::create([
            'mentor_id' => ($mentor ?? $this->mentor)->id,
            'nom' => $nom,
            'cahier_charges_nom' => 'cahier-'.$nom.'.pdf',
            'cahier_charges_chemin' => $chemin,
            'cahier_charges_taille' => 100,
        ]);
    }

    protected function depot(User $utilisateur, Project $projet, array $surcharge = [])
    {
        return $this->actingAs($utilisateur)->post("/projects/{$projet->id}/documents", array_merge([
            'titre' => 'Rapport semaine 1',
            'categorie' => 'Compte rendu',
            'type_rapport' => 'hebdomadaire',
            'fichier' => UploadedFile::fake()->create('rapport.pdf', 100, 'application/pdf'),
        ], $surcharge));
    }

    // ---- Liste et état d'affectation -------------------------------------------------

    public function test_la_liste_affiche_l_etat_reel_d_affectation(): void
    {
        $this->creerProjet('Projet Libre');

        $page = $this->actingAs($this->mentor)->get('/projects');

        $page->assertOk()->assertSee('Projet Alpha')->assertSee('Projet Libre')->assertSee('Non affecté')->assertSee('Affecté');
        $this->assertTrue($this->projet->fresh()->estAffecte());
        $this->assertFalse(Project::where('nom', 'Projet Libre')->first()->estAffecte());
    }

    // ---- Détails du projet -----------------------------------------------------------

    public function test_les_details_affichent_groupe_chef_et_cahier_des_charges(): void
    {
        $this->actingAs($this->mentor)->get("/projects/{$this->projet->id}")
            ->assertOk()
            ->assertSee('Groupe Alpha')
            ->assertSee('Chef Camille')
            ->assertSee('cahier-Projet Alpha.pdf')
            ->assertSee('Aucun rapport soumis pour ce projet.');
    }

    public function test_un_projet_non_affecte_n_affiche_ni_faux_groupe_ni_faux_chef(): void
    {
        $libre = $this->creerProjet('Projet Libre');

        $this->actingAs($this->mentor)->get("/projects/{$libre->id}")
            ->assertOk()
            ->assertSee("Aucun groupe n'est associé à ce projet.", false)
            ->assertDontSee('Chef de groupe')
            ->assertSee('cahier-Projet Libre.pdf');
    }

    public function test_les_rapports_sont_filtres_par_projet(): void
    {
        $autre = $this->creerProjet('Projet Beta');
        $autreChef = User::factory()->create(['role' => 'stagiaire']);
        $groupeBeta = Groupe::create(['nom' => 'Groupe Beta', 'mentor_id' => $this->mentor->id, 'chef_id' => $autreChef->id, 'project_id' => $autre->id]);
        $groupeBeta->membres()->attach($autreChef->id);

        $this->depot($this->chef, $this->projet, ['titre' => 'Rapport Alpha'])->assertRedirect();
        $this->depot($autreChef, $autre, ['titre' => 'Rapport Beta'])->assertRedirect();

        $this->actingAs($this->mentor)->get("/projects/{$this->projet->id}")
            ->assertSee('Rapport Alpha')->assertDontSee('Rapport Beta');
        $this->actingAs($this->mentor)->get("/projects/{$autre->id}")
            ->assertSee('Rapport Beta')->assertDontSee('Rapport Alpha');
    }

    // ---- Accès aux fichiers et au projet ---------------------------------------------

    public function test_le_cahier_des_charges_n_est_telechargeable_que_par_les_utilisateurs_autorises(): void
    {
        $url = "/projects/{$this->projet->id}/cahier-des-charges";

        $this->actingAs($this->mentor)->get($url)->assertOk();
        $this->actingAs($this->membre)->get($url)->assertOk();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get($url)->assertOk();

        // Mentor et administrateur accèdent à tous les documents de l'application, même d'un autre mentor.
        $this->actingAs(User::factory()->create(['role' => 'mentor']))->get($url)->assertOk();
        $this->actingAs(User::factory()->create(['role' => 'stagiaire']))->get($url)->assertForbidden();

        auth()->logout();
        $this->get($url)->assertRedirect('/login');
    }

    public function test_un_mentor_ne_consulte_pas_les_projets_des_autres(): void
    {
        $autreMentor = User::factory()->create(['role' => 'mentor']);

        $this->actingAs($autreMentor)->get("/projects/{$this->projet->id}")->assertForbidden();
        $this->actingAs($autreMentor)->get('/projects')->assertDontSee('Projet Alpha');
    }

    public function test_un_membre_voit_le_projet_de_son_groupe_mais_pas_celui_des_autres(): void
    {
        $autre = $this->creerProjet('Projet Secret');

        $this->actingAs($this->membre)->get('/projects')->assertSee('Projet Alpha')->assertDontSee('Projet Secret');
        $this->actingAs($this->membre)->get("/projects/{$autre->id}")->assertForbidden();
    }

    // ---- Dépôt de documents ----------------------------------------------------------

    public function test_le_chef_de_groupe_depose_un_document_avec_categorie_et_type_de_rapport(): void
    {
        $this->depot($this->chef, $this->projet)->assertRedirect("/projects/{$this->projet->id}");

        $this->assertDatabaseHas('documents', [
            'project_id' => $this->projet->id,
            'titre' => 'Rapport semaine 1',
            'categorie' => 'Compte rendu',
            'type_rapport' => 'hebdomadaire',
        ]);
        $version = Document::firstOrFail()->versions()->firstOrFail();
        $this->assertSame(1, $version->numero_version);
        $this->assertSame($this->chef->id, $version->utilisateur_id);
        Storage::disk('local')->assertExists($version->fichier);
    }

    public function test_un_membre_non_chef_un_mentor_et_un_stagiaire_externe_ne_peuvent_pas_deposer(): void
    {
        foreach ([$this->membre, $this->mentor, User::factory()->create(['role' => 'stagiaire'])] as $utilisateur) {
            $this->depot($utilisateur, $this->projet)->assertForbidden();
        }

        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_versions', 0);
    }

    public function test_le_chef_ne_peut_pas_deposer_pour_le_projet_d_un_autre_groupe(): void
    {
        $autre = $this->creerProjet('Projet Beta');

        $this->depot($this->chef, $autre)->assertForbidden();
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_le_formulaire_de_depot_n_est_visible_que_du_chef(): void
    {
        $this->actingAs($this->chef)->get("/projects/{$this->projet->id}")->assertSee('Déposer un document');
        $this->actingAs($this->membre)->get("/projects/{$this->projet->id}")->assertDontSee('Déposer un document');
        $this->actingAs($this->mentor)->get("/projects/{$this->projet->id}")->assertDontSee('Déposer un document');
    }

    public function test_categorie_type_de_rapport_et_fichier_sont_valides_cote_serveur(): void
    {
        $this->depot($this->chef, $this->projet, ['categorie' => 'Technique'])->assertSessionHasErrors('categorie');
        $this->depot($this->chef, $this->projet, ['type_rapport' => 'mensuel'])->assertSessionHasErrors('type_rapport');
        $this->depot($this->chef, $this->projet, ['titre' => ''])->assertSessionHasErrors('titre');
        $this->depot($this->chef, $this->projet, ['fichier' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')])->assertSessionHasErrors('fichier');
        $this->depot($this->chef, $this->projet, ['fichier' => UploadedFile::fake()->create('gros.pdf', 6000, 'application/pdf')])->assertSessionHasErrors('fichier');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_la_categorie_autre_est_acceptee(): void
    {
        $this->depot($this->chef, $this->projet, ['categorie' => 'Autre'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('documents', ['categorie' => 'Autre']);
    }

    // ---- Versionnement ---------------------------------------------------------------

    public function test_un_nouveau_depot_du_meme_document_cree_une_version_sans_perdre_l_ancienne(): void
    {
        $this->depot($this->chef, $this->projet)->assertRedirect();
        $this->depot($this->chef, $this->projet, ['titre' => 'RAPPORT semaine 1'])->assertRedirect();

        $this->assertDatabaseCount('documents', 1);
        $document = Document::firstOrFail();
        $versions = $document->versions()->orderBy('numero_version')->get();

        $this->assertSame([1, 2], $versions->pluck('numero_version')->all());
        $this->assertNotSame($versions[0]->fichier, $versions[1]->fichier);
        foreach ($versions as $version) {
            Storage::disk('local')->assertExists($version->fichier);
            $this->assertSame($this->chef->id, $version->utilisateur_id);
            $this->assertNotNull($version->created_at);
        }

        $this->actingAs($this->mentor)->get("/projects/{$this->projet->id}")
            ->assertSee('Version actuelle')
            ->assertSee('Ancienne version');
    }

    public function test_un_autre_type_de_rapport_ou_un_autre_nom_cree_un_document_distinct(): void
    {
        $this->depot($this->chef, $this->projet)->assertRedirect();
        $this->depot($this->chef, $this->projet, ['type_rapport' => 'journalier'])->assertRedirect();
        $this->depot($this->chef, $this->projet, ['titre' => 'Autre nom'])->assertRedirect();
        // Même catégorie seule ne suffit pas à former une version du même document.
        $this->depot($this->chef, $this->projet, ['titre' => 'Encore un autre', 'categorie' => 'Compte rendu'])->assertRedirect();

        $this->assertDatabaseCount('documents', 4);
        $this->assertSame(4, DocumentVersion::where('numero_version', 1)->count());
    }

    public function test_un_meme_nom_dans_un_autre_projet_ne_cree_pas_une_version(): void
    {
        $autre = $this->creerProjet('Projet Beta');
        $autreChef = User::factory()->create(['role' => 'stagiaire']);
        Groupe::create(['nom' => 'Groupe Beta', 'mentor_id' => $this->mentor->id, 'chef_id' => $autreChef->id, 'project_id' => $autre->id])
            ->membres()->attach($autreChef->id);

        $this->depot($this->chef, $this->projet)->assertRedirect();
        $this->depot($autreChef, $autre)->assertRedirect();

        $this->assertDatabaseCount('documents', 2);
    }

    // ---- Accès aux fichiers déposés --------------------------------------------------

    public function test_acces_aux_versions_de_documents_de_projet(): void
    {
        $this->depot($this->chef, $this->projet)->assertRedirect();
        $document = Document::firstOrFail();
        $version = $document->versions()->firstOrFail();
        $url = "/documents/{$document->id}/versions/{$version->id}/download";

        $this->actingAs($this->chef)->get($url)->assertOk();
        $this->actingAs($this->membre)->get($url)->assertOk();
        $this->actingAs($this->mentor)->get($url)->assertOk();

        // Tout mentor voit tous les documents ; un stagiaire étranger au projet n'y a pas accès.
        $this->actingAs(User::factory()->create(['role' => 'mentor']))->get($url)->assertOk();
        $this->actingAs(User::factory()->create(['role' => 'stagiaire']))->get($url)->assertForbidden();

        // Une version qui n'appartient pas à ce document n'est pas téléchargeable.
        $this->actingAs($this->chef)->get("/documents/{$document->id}/versions/99999/download")->assertNotFound();
    }

    public function test_le_chemin_de_stockage_n_est_jamais_expose(): void
    {
        $this->depot($this->chef, $this->projet)->assertRedirect();
        $version = Document::firstOrFail()->versions()->firstOrFail();

        $this->actingAs($this->mentor)->get("/projects/{$this->projet->id}")
            ->assertDontSee($version->fichier)
            ->assertDontSee($this->projet->cahier_charges_chemin);
    }
}
