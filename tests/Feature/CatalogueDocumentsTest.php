<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Document;
use App\Models\Groupe;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Page Documents : catalogue complet pour mentor et administrateur, restreint aux projets attribués pour un stagiaire.
 */
class CatalogueDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected User $mentorA;

    protected User $mentorB;

    protected User $admin;

    protected User $chefA;

    protected User $membreA;

    protected User $chefB;

    protected User $etranger;

    protected Project $projetA;

    protected Project $projetB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mentorA = User::factory()->create(['role' => 'mentor', 'name' => 'Mentor Alice']);
        $this->mentorB = User::factory()->create(['role' => 'mentor', 'name' => 'Mentor Bob']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->chefA = User::factory()->create(['role' => 'stagiaire', 'name' => 'Chef Camille']);
        $this->membreA = User::factory()->create(['role' => 'stagiaire', 'name' => 'Membre Michel']);
        $this->chefB = User::factory()->create(['role' => 'stagiaire', 'name' => 'Chef Barnabé']);
        $this->etranger = User::factory()->create(['role' => 'stagiaire']);

        $this->projetA = $this->projet($this->mentorA, 'Projet Alpha', 'Groupe Alpha', $this->chefA, [$this->membreA]);
        $this->projetB = $this->projet($this->mentorB, 'Projet Beta', 'Groupe Beta', $this->chefB, []);
    }

    protected function projet(User $mentor, string $nom, string $groupe, User $chef, array $autres): Project
    {
        $projet = Project::create([
            'mentor_id' => $mentor->id,
            'nom' => $nom,
            'cahier_charges_nom' => 'cdc-'.strtolower($nom).'.pdf',
            'cahier_charges_chemin' => 'projets/cahiers-des-charges/'.strtolower($nom).'.pdf',
            'cahier_charges_taille' => 100,
        ]);
        Groupe::create(['nom' => $groupe, 'mentor_id' => $mentor->id, 'chef_id' => $chef->id, 'project_id' => $projet->id])
            ->membres()->attach(array_merge([$chef->id], array_map(fn (User $u) => $u->id, $autres)));

        return $projet;
    }

    protected function document(Project $projet, User $deposant, string $titre, string $categorie, string $type, ?string $date = null, ?string $fichier = null): Document
    {
        $document = Document::create([
            'utilisateur_id' => $deposant->id,
            'project_id' => $projet->id,
            'titre' => $titre,
            'categorie' => $categorie,
            'type_rapport' => $type,
        ]);
        $version = $document->versions()->create([
            'utilisateur_id' => $deposant->id,
            'numero_version' => 1,
            'fichier' => 'documents/'.md5($titre).'.pdf',
            'nom_fichier' => $fichier ?? $titre.'.pdf',
        ]);
        if ($date) {
            $version->forceFill(['created_at' => $date])->save();
        }

        return $document;
    }

    protected function pieceJointe(Project $projet, ?User $assigne, User $auteur, string $titreTache, string $fichier): Attachment
    {
        $tache = Task::create(['project_id' => $projet->id, 'assigne_id' => $assigne?->id, 'titre' => $titreTache]);

        return Attachment::create([
            'task_id' => $tache->id,
            'utilisateur_id' => $auteur->id,
            'nom_fichier' => $fichier,
            'chemin' => 'attachments/'.md5($fichier).'.pdf',
        ]);
    }

    protected function jeuDeDonnees(): void
    {
        $this->document($this->projetA, $this->chefA, 'Rapport Alpha', 'Compte rendu', 'hebdomadaire');
        $this->document($this->projetB, $this->chefB, 'Rapport Beta', 'Documents d\'analyse', 'journalier');
        $this->pieceJointe($this->projetA, $this->membreA, $this->membreA, 'Tâche du membre', 'rendu-membre.pdf');
        $this->pieceJointe($this->projetA, $this->chefA, $this->chefA, 'Tâche du chef', 'rendu-chef.pdf');

        $ancien = Document::create(['utilisateur_id' => $this->etranger->id, 'titre' => 'Ancien guide personnel']);
        $ancien->versions()->create(['numero_version' => 1, 'fichier' => 'documents/ancien.pdf', 'nom_fichier' => 'ancien.pdf']);
    }

    // ---- Droits d'accès par rôle -----------------------------------------------------

    public function test_mentor_et_admin_voient_tous_les_documents_de_l_application(): void
    {
        $this->jeuDeDonnees();

        foreach ([$this->mentorA, $this->mentorB, $this->admin] as $utilisateur) {
            $this->actingAs($utilisateur)->get('/documents')
                ->assertOk()
                ->assertSee('Rapport Alpha')->assertSee('Rapport Beta')
                ->assertSee('Cahier des charges — Projet Alpha')->assertSee('Cahier des charges — Projet Beta')
                ->assertSee('rendu-membre.pdf')->assertSee('rendu-chef.pdf')
                ->assertSee('Ancien guide personnel');
        }
    }

    public function test_un_stagiaire_membre_ne_voit_que_les_documents_de_son_projet_et_de_ses_taches(): void
    {
        $this->jeuDeDonnees();

        $this->actingAs($this->membreA)->get('/documents')
            ->assertOk()
            ->assertSee('Rapport Alpha')
            ->assertSee('Cahier des charges — Projet Alpha')
            ->assertSee('rendu-membre.pdf')
            // pièce jointe d'une tâche qui ne lui est pas assignée
            ->assertDontSee('rendu-chef.pdf')
            // autre projet et document sans projet
            ->assertDontSee('Rapport Beta')
            ->assertDontSee('Cahier des charges — Projet Beta')
            ->assertDontSee('Ancien guide personnel');
    }

    public function test_le_chef_de_groupe_voit_tous_les_documents_de_son_projet_mais_pas_ceux_des_autres(): void
    {
        $this->jeuDeDonnees();

        $this->actingAs($this->chefA)->get('/documents')
            ->assertOk()
            ->assertSee('Rapport Alpha')->assertSee('rendu-membre.pdf')->assertSee('rendu-chef.pdf')
            ->assertDontSee('Rapport Beta')->assertDontSee('Ancien guide personnel');
    }

    public function test_un_stagiaire_sans_projet_ne_voit_aucun_document(): void
    {
        $this->jeuDeDonnees();

        $this->actingAs($this->etranger)->get('/documents')
            ->assertOk()
            ->assertSee('Aucun document pour le moment.')
            ->assertDontSee('Rapport Alpha')->assertDontSee('Ancien guide personnel');
    }

    public function test_le_filtre_projet_ne_permet_pas_a_un_stagiaire_de_voir_un_autre_projet(): void
    {
        $this->jeuDeDonnees();

        $this->actingAs($this->membreA)->get('/documents?project_id='.$this->projetB->id)
            ->assertOk()
            ->assertDontSee('Rapport Beta')->assertDontSee('Cahier des charges — Projet Beta');
    }

    public function test_les_liens_vers_projet_et_tache_n_apparaissent_que_lorsque_l_acces_est_permis(): void
    {
        $this->jeuDeDonnees();

        // Le mentor A peut ouvrir son projet, pas celui de B : le nom s'affiche mais sans lien.
        $page = $this->actingAs($this->mentorA)->get('/documents');
        $page->assertSee('href="'.route('projects.show', $this->projetA->id).'"', false);
        $page->assertDontSee('href="'.route('projects.show', $this->projetB->id).'"', false);
        $page->assertSee('Projet Beta');
    }

    public function test_aucun_formulaire_d_ajout_dans_la_page_documents(): void
    {
        foreach ([$this->mentorA, $this->admin, $this->chefA, $this->membreA] as $utilisateur) {
            $this->actingAs($utilisateur)->get('/documents')
                ->assertOk()
                ->assertDontSee('Déposer un document')
                ->assertDontSee('multipart/form-data', false);
        }
    }

    // ---- Ce que montre chaque ligne --------------------------------------------------

    public function test_chaque_ligne_indique_ce_qu_est_le_document_a_quoi_il_est_relie_et_par_qui(): void
    {
        $this->jeuDeDonnees();

        $this->actingAs($this->mentorB)->get('/documents?q=Rapport Alpha')
            ->assertOk()
            ->assertSee('Document de projet')
            ->assertSee('Compte rendu')
            ->assertSee('Rapport hebdomadaire')
            ->assertSee('Version 1')
            ->assertSee('Projet Alpha')
            ->assertSee('Groupe Alpha')
            ->assertSee('Chef Camille')
            ->assertSee('Télécharger');

        $this->actingAs($this->mentorB)->get('/documents?q=rendu-membre')
            ->assertSee('Pièce jointe de tâche')
            ->assertSee('Tâche du membre')
            ->assertSee('Membre Michel');
    }

    // ---- Recherche -------------------------------------------------------------------

    public function test_la_recherche_trouve_par_titre_fichier_projet_groupe_tache_et_personne(): void
    {
        $this->document($this->projetA, $this->chefA, 'Synthèse mensuelle', 'Autre', 'hebdomadaire', null, 'zzfichier-unique.pdf');
        $this->document($this->projetB, $this->chefB, 'Planning', 'Autre', 'journalier');
        $this->pieceJointe($this->projetB, $this->chefB, $this->chefB, 'Tâche ultrasecrète', 'piece.pdf');

        $cas = [
            'Synthèse' => ['Synthèse mensuelle', 'Planning'],      // titre
            'zzfichier' => ['Synthèse mensuelle', 'Planning'],     // nom de fichier
            'Projet Beta' => ['Planning', 'Synthèse mensuelle'],   // projet
            'Groupe Alpha' => ['Synthèse mensuelle', 'Planning'],  // groupe
            'ultrasecrète' => ['piece.pdf', 'Synthèse mensuelle'], // tâche
            'Barnabé' => ['Planning', 'Synthèse mensuelle'],       // personne
        ];

        foreach ($cas as $terme => [$attendu, $absent]) {
            $this->actingAs($this->admin)->get('/documents?q='.urlencode($terme))
                ->assertOk()->assertSee($attendu)->assertDontSee($absent);
        }
    }

    public function test_une_recherche_sans_resultat_affiche_un_message_clair(): void
    {
        $this->jeuDeDonnees();

        $this->actingAs($this->admin)->get('/documents?q=introuvable-xyz')
            ->assertOk()
            ->assertSee('Aucun document ne correspond à votre recherche.');
    }

    public function test_la_recherche_d_un_stagiaire_reste_limitee_a_ses_projets(): void
    {
        $this->jeuDeDonnees();

        $this->actingAs($this->membreA)->get('/documents?q=Rapport')
            ->assertSee('Rapport Alpha')->assertDontSee('Rapport Beta');
    }

    // ---- Filtres ---------------------------------------------------------------------

    public function test_filtre_par_origine(): void
    {
        $this->jeuDeDonnees();

        $this->actingAs($this->admin)->get('/documents?origine=cahier')
            ->assertSee('Cahier des charges — Projet Alpha')
            ->assertDontSee('Rapport Alpha')->assertDontSee('rendu-chef.pdf')->assertDontSee('Ancien guide personnel');

        $this->actingAs($this->admin)->get('/documents?origine=tache')
            ->assertSee('rendu-chef.pdf')->assertDontSee('Rapport Alpha')->assertDontSee('Cahier des charges — Projet');

        $this->actingAs($this->admin)->get('/documents?origine=projet')
            ->assertSee('Rapport Alpha')->assertDontSee('Ancien guide personnel')->assertDontSee('rendu-chef.pdf');

        $this->actingAs($this->admin)->get('/documents?origine=personnel')
            ->assertSee('Ancien guide personnel')->assertDontSee('Rapport Alpha');
    }

    public function test_filtre_par_projet(): void
    {
        $this->jeuDeDonnees();

        $this->actingAs($this->admin)->get('/documents?project_id='.$this->projetB->id)
            ->assertSee('Rapport Beta')->assertSee('Cahier des charges — Projet Beta')
            ->assertDontSee('Rapport Alpha')->assertDontSee('rendu-chef.pdf')->assertDontSee('Ancien guide personnel');
    }

    public function test_filtre_par_categorie_et_par_type_de_rapport(): void
    {
        $this->jeuDeDonnees();

        $this->actingAs($this->admin)->get('/documents?categorie='.urlencode('Compte rendu'))
            ->assertSee('Rapport Alpha')->assertDontSee('Rapport Beta')
            // la catégorie n'existe pas pour les cahiers des charges ni les pièces jointes
            ->assertDontSee('Cahier des charges — Projet')->assertDontSee('rendu-chef.pdf');

        $this->actingAs($this->admin)->get('/documents?type_rapport=journalier')
            ->assertSee('Rapport Beta')->assertDontSee('Rapport Alpha');
    }

    public function test_filtre_par_periode_de_depot(): void
    {
        $this->document($this->projetA, $this->chefA, 'Document de janvier', 'Autre', 'hebdomadaire', '2026-01-10 10:00:00');
        $this->document($this->projetA, $this->chefA, 'Document de mars', 'Autre', 'hebdomadaire', '2026-03-10 10:00:00');

        $this->actingAs($this->admin)->get('/documents?date_debut=2026-03-01&date_fin=2026-03-31&origine=projet')
            ->assertSee('Document de mars')->assertDontSee('Document de janvier');

        $this->actingAs($this->admin)->get('/documents?date_fin=2026-02-01&origine=projet')
            ->assertSee('Document de janvier')->assertDontSee('Document de mars');
    }

    public function test_les_filtres_se_combinent_et_restent_affiches(): void
    {
        $this->jeuDeDonnees();

        $this->actingAs($this->admin)->get('/documents?q=Rapport&project_id='.$this->projetA->id.'&type_rapport=hebdomadaire')
            ->assertOk()
            ->assertSee('Rapport Alpha')->assertDontSee('Rapport Beta')
            ->assertSee('value="Rapport"', false)
            ->assertSee('Réinitialiser les filtres');
    }

    public function test_des_filtres_invalides_sont_refuses(): void
    {
        $this->actingAs($this->admin)->get('/documents?origine=n-importe-quoi')->assertSessionHasErrors('origine');
        $this->actingAs($this->admin)->get('/documents?categorie=Inconnue')->assertSessionHasErrors('categorie');
        $this->actingAs($this->admin)->get('/documents?type_rapport=mensuel')->assertSessionHasErrors('type_rapport');
        $this->actingAs($this->admin)->get('/documents?date_debut=2026-05-01&date_fin=2026-04-01')->assertSessionHasErrors('date_fin');
        $this->actingAs($this->admin)->get('/documents?q='.str_repeat('a', 101))->assertSessionHasErrors('q');
    }

    // ---- Pagination ------------------------------------------------------------------

    public function test_la_pagination_conserve_la_recherche(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->document($this->projetA, $this->chefA, sprintf('Compte-rendu %02d', $i), 'Compte rendu', 'hebdomadaire', "2026-02-{$i} 10:00:00");
        }

        $page1 = $this->actingAs($this->admin)->get('/documents?q=Compte-rendu');
        $page1->assertOk()->assertSee('Compte-rendu 12')->assertDontSee('Compte-rendu 01');
        $page1->assertSee('q=Compte-rendu', false)->assertSee('page=2', false);

        $this->actingAs($this->admin)->get('/documents?q=Compte-rendu&page=2')
            ->assertOk()->assertSee('Compte-rendu 01')->assertSee('Compte-rendu 02')->assertDontSee('Compte-rendu 12');
    }

    // ---- Tableau de bord et accès non connecté ---------------------------------------

    public function test_le_compteur_de_documents_du_stagiaire_compte_les_documents_de_ses_projets(): void
    {
        $this->jeuDeDonnees();

        $indicateurs = app(DashboardService::class)->indicateursPour($this->membreA);
        $this->assertSame(1, $indicateurs['mes_documents']);

        $this->assertSame(0, app(DashboardService::class)->indicateursPour($this->etranger)['mes_documents']);
    }

    public function test_la_page_documents_exige_une_connexion(): void
    {
        $this->get('/documents')->assertRedirect('/login');
    }
}
