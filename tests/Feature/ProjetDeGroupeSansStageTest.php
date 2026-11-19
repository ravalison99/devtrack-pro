<?php

namespace Tests\Feature;

use App\Models\Groupe;
use App\Models\Project;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskStatusChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Les projets du nouveau flux n'ont pas de stage : tout ce qui lisait project->stage doit le supporter.
 */
class ProjetDeGroupeSansStageTest extends TestCase
{
    use RefreshDatabase;

    protected function groupeAvecProjet(): array
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $chef = User::factory()->create(['role' => 'stagiaire']);
        $membre = User::factory()->create(['role' => 'stagiaire']);
        $projet = Project::create(['mentor_id' => $mentor->id, 'nom' => 'Projet de groupe']);
        Groupe::create(['nom' => 'G', 'mentor_id' => $mentor->id, 'chef_id' => $chef->id, 'project_id' => $projet->id])
            ->membres()->attach([$chef->id, $membre->id]);

        return [$mentor, $chef, $membre, $projet];
    }

    public function test_creer_une_tache_sur_un_projet_de_groupe_notifie_l_assigne(): void
    {
        Notification::fake();
        [$mentor, $chef, $membre, $projet] = $this->groupeAvecProjet();

        $this->actingAs($mentor)->post('/tasks', [
            'project_id' => $projet->id,
            'assigne_id' => $membre->id,
            'titre' => 'Première tâche',
            'priorite' => 'moyenne',
        ])->assertRedirect('/tasks');

        $this->assertDatabaseHas('tasks', ['project_id' => $projet->id, 'titre' => 'Première tâche']);
        Notification::assertSentTo($membre, TaskAssignedNotification::class);
        Notification::assertNotSentTo($chef, TaskAssignedNotification::class);
        Notification::assertNotSentTo($mentor, TaskAssignedNotification::class);
    }

    public function test_changer_le_statut_d_une_tache_de_groupe_notifie_le_mentor_et_l_assigne_mais_pas_l_auteur(): void
    {
        Notification::fake();
        [$mentor, $chef, $membre, $projet] = $this->groupeAvecProjet();
        $tache = Task::create(['project_id' => $projet->id, 'assigne_id' => $membre->id, 'titre' => 'T']);

        $this->actingAs($chef)->patch("/tasks/{$tache->id}/status", ['statut' => 'en_cours'])->assertRedirect();

        $this->assertSame('en_cours', $tache->fresh()->statut);
        Notification::assertSentTo([$mentor, $membre], TaskStatusChangedNotification::class);
        // Le chef vient de changer le statut : il n'est pas notifié de sa propre action.
        Notification::assertNotSentTo($chef, TaskStatusChangedNotification::class);
    }

    public function test_les_membres_du_groupe_accedent_aux_taches_et_au_kanban_de_leur_projet(): void
    {
        [$mentor, $chef, $membre, $projet] = $this->groupeAvecProjet();
        $tache = Task::create(['project_id' => $projet->id, 'assigne_id' => $membre->id, 'titre' => 'T']);

        $this->actingAs($membre)->get("/projects/{$projet->id}/kanban")->assertOk();
        $this->actingAs($membre)->get("/tasks/{$tache->id}")->assertOk();
        $this->actingAs($membre)->get('/tasks')->assertOk()->assertSee('T');
        $this->actingAs($mentor)->get('/tasks/create')->assertOk()->assertSee('Projet de groupe');
        $this->actingAs($membre)->get('/dashboard')->assertOk();
    }

    public function test_la_liste_des_stages_supporte_un_stagiaire_ou_un_mentor_disparu(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Simule la base de développement : stage dont les comptes ont disparu (tables MyISAM, sans clés étrangères).
        // La vérification des clés est reportée au commit, qui n'a jamais lieu dans un test.
        DB::statement('PRAGMA defer_foreign_keys = ON');
        DB::table('stages')->insert([
            'stagiaire_id' => 98001,
            'mentor_id' => 98002,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
            'statut' => 'en_cours',
        ]);

        $this->actingAs($admin)->get('/stages')->assertOk()->assertSee('Compte supprimé');
    }
}
