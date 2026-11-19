<?php

namespace Tests\Feature;

use App\Models\Groupe;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Le chef de groupe répartit les tâches du projet confié à son groupe ;
 * un membre ne voit que les tâches qui lui sont assignées.
 */
class TachesParChefDeGroupeTest extends TestCase
{
    use RefreshDatabase;

    protected User $mentor;

    protected User $chef;

    protected User $userB;

    protected User $userC;

    protected Project $projet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mentor = User::factory()->create(['role' => 'mentor']);
        $this->chef = User::factory()->create(['role' => 'stagiaire', 'name' => 'Chef Camille']);
        $this->userB = User::factory()->create(['role' => 'stagiaire', 'name' => 'Bruno B']);
        $this->userC = User::factory()->create(['role' => 'stagiaire', 'name' => 'Clara C']);

        $this->projet = Project::create(['mentor_id' => $this->mentor->id, 'nom' => 'Projet A']);
        Groupe::create(['nom' => 'G', 'mentor_id' => $this->mentor->id, 'chef_id' => $this->chef->id, 'project_id' => $this->projet->id])
            ->membres()->attach([$this->chef->id, $this->userB->id, $this->userC->id]);
    }

    protected function tache(string $titre, ?User $assigne, ?Project $projet = null): Task
    {
        return Task::create([
            'project_id' => ($projet ?? $this->projet)->id,
            'assigne_id' => $assigne?->id,
            'titre' => $titre,
        ]);
    }

    protected function creer(User $auteur, array $surcharge = [])
    {
        return $this->actingAs($auteur)->post('/tasks', array_merge([
            'project_id' => $this->projet->id,
            'assigne_id' => $this->userB->id,
            'titre' => 'Écrire le module',
            'priorite' => 'moyenne',
        ], $surcharge));
    }

    // ---- Création et assignation -----------------------------------------------------

    public function test_le_chef_cree_une_tache_et_l_assigne_a_un_membre_qui_est_notifie(): void
    {
        Notification::fake();

        $this->creer($this->chef)->assertRedirect('/tasks');

        $this->assertDatabaseHas('tasks', [
            'project_id' => $this->projet->id,
            'assigne_id' => $this->userB->id,
            'titre' => 'Écrire le module',
        ]);
        Notification::assertSentTo($this->userB, TaskAssignedNotification::class);
        Notification::assertNotSentTo($this->chef, TaskAssignedNotification::class);
    }

    public function test_le_chef_peut_s_assigner_une_tache_a_lui_meme(): void
    {
        $this->creer($this->chef, ['assigne_id' => $this->chef->id])->assertRedirect('/tasks');

        $this->assertDatabaseHas('tasks', ['assigne_id' => $this->chef->id]);
    }

    public function test_le_formulaire_du_chef_ne_propose_que_son_projet_et_les_membres_de_son_groupe(): void
    {
        $autre = Project::create(['mentor_id' => $this->mentor->id, 'nom' => 'Projet Autre']);
        $etranger = User::factory()->create(['role' => 'stagiaire', 'name' => 'Etranger Z']);
        $autreChef = User::factory()->create(['role' => 'stagiaire']);
        Groupe::create(['nom' => 'G2', 'mentor_id' => $this->mentor->id, 'chef_id' => $autreChef->id, 'project_id' => $autre->id])
            ->membres()->attach([$autreChef->id, $etranger->id]);

        $this->actingAs($this->chef)->get('/tasks/create')
            ->assertOk()
            ->assertSee('Projet A')->assertSee('Bruno B')->assertSee('Clara C')
            ->assertDontSee('Projet Autre')->assertDontSee('Etranger Z');
    }

    public function test_le_bouton_creer_une_tache_est_accessible_au_chef_depuis_le_tableau_de_bord_et_le_projet(): void
    {
        $lien = route('tasks.create');

        $this->actingAs($this->chef)->get('/dashboard')->assertOk()->assertSee($lien, false);
        $this->actingAs($this->chef)->get('/tasks')->assertOk()->assertSee($lien, false);
        $this->actingAs($this->chef)->get("/projects/{$this->projet->id}")
            ->assertOk()->assertSee(route('tasks.create', ['project_id' => $this->projet->id]), false);

        // Le formulaire s'ouvre avec le projet présélectionné.
        $this->actingAs($this->chef)->get("/tasks/create?project_id={$this->projet->id}")->assertOk()->assertSee('Assigner à');

        // Un membre non chef n'a aucun de ces boutons.
        $this->actingAs($this->userB)->get('/dashboard')->assertDontSee($lien, false);
        $this->actingAs($this->userB)->get('/tasks')->assertDontSee($lien, false);
        $this->actingAs($this->userB)->get("/projects/{$this->projet->id}")
            ->assertOk()->assertDontSee(route('tasks.create', ['project_id' => $this->projet->id]), false);
    }

    public function test_un_membre_non_chef_ne_peut_ni_voir_le_formulaire_ni_creer_de_tache(): void
    {
        $this->actingAs($this->userB)->get('/tasks/create')->assertForbidden();
        $this->creer($this->userB, ['assigne_id' => $this->userB->id])->assertForbidden();

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_le_chef_d_un_autre_groupe_ne_peut_pas_creer_de_tache_sur_ce_projet(): void
    {
        $autreChef = User::factory()->create(['role' => 'stagiaire']);
        $autre = Project::create(['mentor_id' => $this->mentor->id, 'nom' => 'Projet Autre']);
        Groupe::create(['nom' => 'G2', 'mentor_id' => $this->mentor->id, 'chef_id' => $autreChef->id, 'project_id' => $autre->id])
            ->membres()->attach($autreChef->id);

        $this->creer($autreChef)->assertForbidden();
        $this->actingAs($autreChef)->get("/tasks/create?project_id={$this->projet->id}")->assertForbidden();
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_l_assigne_est_obligatoire_et_doit_appartenir_au_groupe(): void
    {
        $etranger = User::factory()->create(['role' => 'stagiaire']);

        $this->creer($this->chef, ['assigne_id' => null])->assertSessionHasErrors('assigne_id');
        $this->creer($this->chef, ['assigne_id' => $etranger->id])->assertSessionHasErrors('assigne_id');
        $this->creer($this->chef, ['assigne_id' => $this->mentor->id])->assertSessionHasErrors('assigne_id');

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_le_mentor_peut_aussi_creer_une_tache_pour_un_membre(): void
    {
        $this->creer($this->mentor, ['assigne_id' => $this->userC->id])->assertRedirect('/tasks');

        $this->assertDatabaseHas('tasks', ['assigne_id' => $this->userC->id]);
    }

    // ---- Visibilité ------------------------------------------------------------------

    public function test_un_membre_ne_voit_que_les_taches_qui_lui_sont_assignees(): void
    {
        $this->tache('Tache pour Bruno', $this->userB);
        $this->tache('Tache pour Clara', $this->userC);
        $this->tache('Tache du chef', $this->chef);

        $this->actingAs($this->userB)->get('/tasks')
            ->assertOk()
            ->assertSee('Tache pour Bruno')
            ->assertDontSee('Tache pour Clara')
            ->assertDontSee('Tache du chef');
    }

    public function test_un_membre_ne_peut_pas_ouvrir_le_detail_d_une_tache_d_un_autre(): void
    {
        $sienne = $this->tache('Sienne', $this->userB);
        $autre = $this->tache('Autre', $this->userC);

        $this->actingAs($this->userB)->get("/tasks/{$sienne->id}")->assertOk()->assertSee('Bruno B');
        $this->actingAs($this->userB)->get("/tasks/{$autre->id}")->assertForbidden();
    }

    public function test_le_kanban_d_un_membre_ne_montre_que_ses_taches(): void
    {
        $this->tache('Tache pour Bruno', $this->userB);
        $this->tache('Tache pour Clara', $this->userC);

        $this->actingAs($this->userB)->get("/projects/{$this->projet->id}/kanban")
            ->assertOk()
            ->assertSee('Tache pour Bruno')
            ->assertDontSee('Tache pour Clara');
    }

    public function test_le_chef_et_le_mentor_voient_toutes_les_taches_du_projet(): void
    {
        $this->tache('Tache pour Bruno', $this->userB);
        $this->tache('Tache pour Clara', $this->userC);

        foreach ([$this->chef, $this->mentor] as $utilisateur) {
            $this->actingAs($utilisateur)->get('/tasks')->assertSee('Tache pour Bruno')->assertSee('Tache pour Clara');
            $this->actingAs($utilisateur)->get("/projects/{$this->projet->id}/kanban")->assertSee('Tache pour Bruno')->assertSee('Tache pour Clara');
        }
    }

    public function test_un_stagiaire_d_un_autre_groupe_ne_voit_aucune_tache_du_projet(): void
    {
        $tache = $this->tache('Tache pour Bruno', $this->userB);
        $etranger = User::factory()->create(['role' => 'stagiaire']);

        $this->actingAs($etranger)->get('/tasks')->assertDontSee('Tache pour Bruno');
        $this->actingAs($etranger)->get("/tasks/{$tache->id}")->assertForbidden();
        $this->actingAs($etranger)->get("/projects/{$this->projet->id}/kanban")->assertForbidden();
    }

    public function test_le_tableau_de_bord_d_un_membre_ne_compte_que_ses_taches(): void
    {
        $this->tache('B1', $this->userB);
        $this->tache('B2', $this->userB);
        $this->tache('C1', $this->userC);

        $indicateurs = app(DashboardService::class)->indicateursPour($this->userB);
        $this->assertSame(2, $indicateurs['taches_a_faire']);

        $indicateursChef = app(DashboardService::class)->indicateursPour($this->chef);
        $this->assertSame(3, $indicateursChef['taches_a_faire']);
    }

    // ---- Statuts et pièces jointes ---------------------------------------------------

    public function test_un_membre_fait_progresser_sa_tache_mais_ne_peut_pas_la_terminer(): void
    {
        $tache = $this->tache('Sienne', $this->userB);

        $this->actingAs($this->userB)->patch("/tasks/{$tache->id}/status", ['statut' => 'en_cours'])->assertRedirect();
        $this->assertSame('en_cours', $tache->fresh()->statut);

        $this->actingAs($this->userB)->patch("/tasks/{$tache->id}/status", ['statut' => 'termine'])->assertForbidden();
    }

    public function test_un_membre_ne_peut_pas_changer_le_statut_de_la_tache_d_un_autre(): void
    {
        $autre = $this->tache('Autre', $this->userC);

        $this->actingAs($this->userB)->patch("/tasks/{$autre->id}/status", ['statut' => 'en_cours'])->assertForbidden();
        $this->assertSame('a_faire', $autre->fresh()->statut);
    }

    public function test_une_piece_jointe_n_est_acceptee_que_pour_une_tache_visible(): void
    {
        Storage::fake('local');
        $sienne = $this->tache('Sienne', $this->userB);
        $autre = $this->tache('Autre', $this->userC);

        $this->actingAs($this->userB)
            ->post("/tasks/{$sienne->id}/attachments", ['fichier' => UploadedFile::fake()->create('a.pdf', 50, 'application/pdf')])
            ->assertRedirect();
        $this->actingAs($this->userB)
            ->post("/tasks/{$autre->id}/attachments", ['fichier' => UploadedFile::fake()->create('b.pdf', 50, 'application/pdf')])
            ->assertForbidden();

        $this->assertDatabaseCount('attachments', 1);
    }
}
