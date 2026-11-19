<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Groupe;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Détail d'une tâche : l'assigné rend un fichier, et toute personne autorisée à voir la tâche peut y écrire.
 */
class DiscussionEtRenduDeTacheTest extends TestCase
{
    use RefreshDatabase;

    protected User $mentor;

    protected User $admin;

    protected User $chef;

    protected User $assigne;

    protected User $autreMembre;

    protected User $etranger;

    protected Task $tache;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->mentor = User::factory()->create(['role' => 'mentor', 'name' => 'Mentor Martin']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->chef = User::factory()->create(['role' => 'stagiaire', 'name' => 'Chef Camille']);
        $this->assigne = User::factory()->create(['role' => 'stagiaire', 'name' => 'Bruno Assigné']);
        $this->autreMembre = User::factory()->create(['role' => 'stagiaire', 'name' => 'Clara Autre']);
        $this->etranger = User::factory()->create(['role' => 'stagiaire']);

        $projet = Project::create(['mentor_id' => $this->mentor->id, 'nom' => 'Projet A']);
        Groupe::create(['nom' => 'G', 'mentor_id' => $this->mentor->id, 'chef_id' => $this->chef->id, 'project_id' => $projet->id])
            ->membres()->attach([$this->chef->id, $this->assigne->id, $this->autreMembre->id]);

        $this->tache = Task::create(['project_id' => $projet->id, 'assigne_id' => $this->assigne->id, 'titre' => 'Rédiger le rapport']);
    }

    protected function fichier(string $nom = 'rendu.pdf', int $ko = 100): UploadedFile
    {
        return UploadedFile::fake()->create($nom, $ko, 'application/pdf');
    }

    // ---- Commentaires ----------------------------------------------------------------

    public function test_l_assigne_laisse_un_commentaire_visible_avec_son_nom_et_la_date(): void
    {
        $this->actingAs($this->assigne)
            ->post("/tasks/{$this->tache->id}/comments", ['contenu' => 'Voici ma première version.'])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'task_id' => $this->tache->id,
            'utilisateur_id' => $this->assigne->id,
            'contenu' => 'Voici ma première version.',
        ]);

        $this->actingAs($this->assigne)->get("/tasks/{$this->tache->id}")
            ->assertOk()
            ->assertSee('Voici ma première version.')
            ->assertSee('Bruno Assigné')
            ->assertSee(now()->format('d/m/Y'));
    }

    public function test_la_discussion_entre_responsables_et_stagiaire_s_affiche_dans_l_ordre(): void
    {
        $this->actingAs($this->assigne)->post("/tasks/{$this->tache->id}/comments", ['contenu' => 'Premier message']);
        $this->actingAs($this->mentor)->post("/tasks/{$this->tache->id}/comments", ['contenu' => 'Deuxième message']);
        $this->actingAs($this->chef)->post("/tasks/{$this->tache->id}/comments", ['contenu' => 'Troisième message']);
        $this->actingAs($this->admin)->post("/tasks/{$this->tache->id}/comments", ['contenu' => 'Quatrième message']);

        $this->assertDatabaseCount('comments', 4);

        $this->actingAs($this->mentor)->get("/tasks/{$this->tache->id}")
            ->assertSeeInOrder(['Premier message', 'Deuxième message', 'Troisième message', 'Quatrième message']);
    }

    public function test_une_personne_qui_ne_voit_pas_la_tache_ne_peut_pas_y_ecrire(): void
    {
        foreach ([$this->autreMembre, $this->etranger, User::factory()->create(['role' => 'mentor'])] as $utilisateur) {
            $this->actingAs($utilisateur)
                ->post("/tasks/{$this->tache->id}/comments", ['contenu' => 'Intrusion'])
                ->assertForbidden();
        }

        auth()->logout();
        $this->post("/tasks/{$this->tache->id}/comments", ['contenu' => 'Anonyme'])->assertRedirect('/login');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_un_commentaire_vide_ou_trop_long_est_refuse(): void
    {
        $this->actingAs($this->assigne)->post("/tasks/{$this->tache->id}/comments", ['contenu' => ''])->assertSessionHasErrors('contenu');
        $this->actingAs($this->assigne)->post("/tasks/{$this->tache->id}/comments", ['contenu' => str_repeat('a', 2001)])->assertSessionHasErrors('contenu');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_le_contenu_d_un_commentaire_est_echappe(): void
    {
        $this->actingAs($this->assigne)->post("/tasks/{$this->tache->id}/comments", ['contenu' => '<script>alert(1)</script>']);

        $this->actingAs($this->assigne)->get("/tasks/{$this->tache->id}")
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    // ---- Rendu de fichier ------------------------------------------------------------

    public function test_l_assigne_rend_un_fichier_enregistre_avec_son_auteur(): void
    {
        $this->actingAs($this->assigne)
            ->post("/tasks/{$this->tache->id}/attachments", ['fichier' => $this->fichier('rapport-final.pdf')])
            ->assertRedirect();

        $piece = Attachment::firstOrFail();
        $this->assertSame($this->tache->id, $piece->task_id);
        $this->assertSame($this->assigne->id, $piece->utilisateur_id);
        $this->assertSame('rapport-final.pdf', $piece->nom_fichier);
        Storage::disk('local')->assertExists($piece->chemin);

        $this->actingAs($this->mentor)->get("/tasks/{$this->tache->id}")
            ->assertSee('rapport-final.pdf')
            ->assertSee('déposé par Bruno Assigné');
    }

    public function test_type_et_taille_du_fichier_rendu_sont_controles(): void
    {
        $this->actingAs($this->assigne)
            ->post("/tasks/{$this->tache->id}/attachments", ['fichier' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')])
            ->assertSessionHasErrors('fichier');
        $this->actingAs($this->assigne)
            ->post("/tasks/{$this->tache->id}/attachments", ['fichier' => $this->fichier('gros.pdf', 6000)])
            ->assertSessionHasErrors('fichier');

        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_une_personne_qui_ne_voit_pas_la_tache_ne_peut_pas_y_deposer_de_fichier(): void
    {
        $this->actingAs($this->autreMembre)
            ->post("/tasks/{$this->tache->id}/attachments", ['fichier' => $this->fichier()])
            ->assertForbidden();

        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_les_pieces_jointes_ne_sont_telechargeables_que_par_les_personnes_autorisees(): void
    {
        $this->actingAs($this->assigne)->post("/tasks/{$this->tache->id}/attachments", ['fichier' => $this->fichier()]);
        $piece = Attachment::firstOrFail();
        $url = "/tasks/{$this->tache->id}/attachments/{$piece->id}/download";

        // Mentor (même d'un autre projet) et admin accèdent à tous les fichiers de l'application.
        foreach ([$this->assigne, $this->chef, $this->mentor, $this->admin, User::factory()->create(['role' => 'mentor'])] as $utilisateur) {
            $this->actingAs($utilisateur)->get($url)->assertOk();
        }

        foreach ([$this->autreMembre, $this->etranger] as $utilisateur) {
            $this->actingAs($utilisateur)->get($url)->assertForbidden();
        }

        auth()->logout();
        $this->get($url)->assertRedirect('/login');
    }

    public function test_une_piece_jointe_d_une_autre_tache_est_introuvable_via_cette_tache(): void
    {
        $autreTache = Task::create(['project_id' => $this->tache->project_id, 'assigne_id' => $this->assigne->id, 'titre' => 'Autre']);
        $this->actingAs($this->assigne)->post("/tasks/{$autreTache->id}/attachments", ['fichier' => $this->fichier()]);
        $piece = Attachment::firstOrFail();

        $this->actingAs($this->assigne)->get("/tasks/{$this->tache->id}/attachments/{$piece->id}/download")->assertNotFound();
    }

    public function test_un_fichier_disparu_du_stockage_donne_un_404_et_non_une_erreur(): void
    {
        $this->actingAs($this->assigne)->post("/tasks/{$this->tache->id}/attachments", ['fichier' => $this->fichier()]);
        $piece = Attachment::firstOrFail();
        Storage::disk('local')->delete($piece->chemin);

        $this->actingAs($this->assigne)->get("/tasks/{$this->tache->id}/attachments/{$piece->id}/download")->assertNotFound();
    }

    public function test_le_chemin_de_stockage_n_est_jamais_affiche(): void
    {
        $this->actingAs($this->assigne)->post("/tasks/{$this->tache->id}/attachments", ['fichier' => $this->fichier()]);
        $piece = Attachment::firstOrFail();

        $this->actingAs($this->assigne)->get("/tasks/{$this->tache->id}")->assertDontSee($piece->chemin);
    }
}
