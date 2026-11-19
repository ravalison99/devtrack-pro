<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected function creerTache(): Task
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        $stage = Stage::create([
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
            'statut' => 'en_cours',
        ]);

        $project = Project::create(['stage_id' => $stage->id, 'nom' => 'Projet de test']);

        return Task::create([
            'project_id' => $project->id,
            'titre' => 'Tâche de test',
            'priorite' => 'haute',
            'date_echeance' => now()->subDay(),
        ]);
    }

    public function test_un_administrateur_voit_toutes_les_listes(): void
    {
        $tache = $this->creerTache();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/stages')->assertOk()->assertSee($tache->project->stage->stagiaire->name);
        $this->actingAs($admin)->get('/projects')->assertOk()->assertSee('Projet de test');
        $this->actingAs($admin)->get('/tasks')->assertOk()->assertSee('Tâche de test');
        $this->actingAs($admin)->get('/stages/create')->assertOk();
        $this->actingAs($admin)->get('/tasks/create')->assertOk();
    }

    public function test_un_mentor_peut_filtrer_ses_stages_par_stagiaire_et_par_dates(): void
    {
        $stage = $this->creerTache()->project->stage;

        $response = $this->actingAs($stage->mentor)->get('/stages?'.http_build_query([
            'stagiaire_id' => $stage->stagiaire_id,
            'date_debut' => '2025-12-01',
            'date_fin' => '2026-04-01',
        ]));

        $response->assertOk()->assertSee($stage->stagiaire->email);
    }

    public function test_un_mentor_voit_les_formulaires_preremplis_et_ses_taches_en_retard(): void
    {
        $tache = $this->creerTache();
        $stage = $tache->project->stage;
        $mentor = $stage->mentor;

        $this->actingAs($mentor)->get('/projects/create')->assertOk();
        $this->actingAs($mentor)->get("/tasks/create?project_id={$tache->project_id}")->assertOk();
        $this->actingAs($mentor)->get('/dashboard')->assertOk()->assertSee('Tâches en retard');
    }

    public function test_le_detail_dune_tache_affiche_ses_commentaires(): void
    {
        $tache = $this->creerTache();
        $mentor = $tache->project->stage->mentor;

        Comment::create(['task_id' => $tache->id, 'utilisateur_id' => $mentor->id, 'contenu' => 'Bon travail']);

        $this->actingAs($mentor)->get("/tasks/{$tache->id}")
            ->assertOk()
            ->assertSee('Bon travail')
            ->assertSee($mentor->name);
    }

    public function test_un_stagiaire_consulte_ses_documents_et_notifications(): void
    {
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        $this->actingAs($stagiaire)->get('/documents')->assertOk();
        $this->actingAs($stagiaire)->get('/notifications')->assertOk();
    }

    public function test_un_administrateur_peut_ouvrir_le_formulaire_de_modification(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cible = User::factory()->create(['role' => 'mentor', 'name' => 'Mentor Cible']);

        $this->actingAs($admin)->get("/users/{$cible->id}/edit")->assertOk()->assertSee('Mentor Cible');
    }

    public function test_un_tri_invalide_revient_au_tri_par_nom_croissant(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Zoe']);
        User::factory()->create(['name' => 'Alice']);

        $response = $this->actingAs($admin)->get('/users?sort=password&direction=n_importe_quoi');

        $response->assertOk()->assertSeeInOrder(['Alice', 'Zoe']);
    }
}
