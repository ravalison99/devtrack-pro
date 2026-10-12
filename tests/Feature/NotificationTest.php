<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Stage;
use App\Models\User;
use App\Notifications\NewUserNotification;
use App\Notifications\StageAssignedNotification;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\WeeklyReportSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_mentor_recoit_une_notification_quand_un_rapport_est_soumis(): void
    {
        Storage::fake('local');
        Notification::fake();

        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        Stage::create([
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
        ]);

        $this->actingAs($stagiaire)->post('/reports', [
            'semaine' => 3,
            'contenu' => 'Contenu du rapport.',
        ]);

        Notification::assertSentTo($mentor, WeeklyReportSubmittedNotification::class);
    }

    public function test_un_mentor_non_lie_ne_recoit_pas_la_notification(): void
    {
        Storage::fake('local');
        Notification::fake();

        $mentorNonLie = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        $this->actingAs($stagiaire)->post('/reports', [
            'semaine' => 3,
            'contenu' => 'Contenu du rapport.',
        ]);

        Notification::assertNotSentTo($mentorNonLie, WeeklyReportSubmittedNotification::class);
    }

    public function test_le_mentor_et_le_stagiaire_recoivent_une_notification_a_la_creation_du_stage(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        $mentorTiers = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        $this->actingAs($admin)->post('/stages', [
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-09-01',
            'date_fin' => '2026-11-01',
            'mode_travail' => 'presentiel',
        ]);

        Notification::assertSentTo($mentor, StageAssignedNotification::class);
        Notification::assertSentTo($stagiaire, StageAssignedNotification::class);
        Notification::assertNotSentTo($mentorTiers, StageAssignedNotification::class);
    }

    public function test_le_stagiaire_recoit_une_notification_quand_une_tache_lui_est_assignee(): void
    {
        Notification::fake();

        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $autreStagiaire = User::factory()->create(['role' => 'stagiaire']);

        $stage = Stage::create([
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
        ]);
        $project = Project::create(['stage_id' => $stage->id, 'nom' => 'Projet']);

        $this->actingAs($mentor)->post('/tasks', [
            'project_id' => $project->id,
            'titre' => 'Nouvelle tâche',
            'priorite' => 'haute',
        ]);

        Notification::assertSentTo($stagiaire, TaskAssignedNotification::class);
        Notification::assertNotSentTo($autreStagiaire, TaskAssignedNotification::class);
    }

    public function test_les_admins_recoivent_une_notification_a_la_creation_dun_utilisateur(): void
    {
        Notification::fake();

        $admin1 = User::factory()->create(['role' => 'admin']);
        $admin2 = User::factory()->create(['role' => 'admin']);
        $mentor = User::factory()->create(['role' => 'mentor']);

        $this->actingAs($admin1)->post('/users', [
            'name' => 'Nouveau Membre',
            'email' => 'nouveau.membre@example.com',
            'password' => 'motdepasse123',
            'role' => 'stagiaire',
        ]);

        Notification::assertSentTo($admin2, NewUserNotification::class);
        Notification::assertNotSentTo($mentor, NewUserNotification::class);
    }

    public function test_un_utilisateur_peut_supprimer_sa_propre_notification(): void
    {
        Storage::fake('local');

        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        Stage::create([
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
        ]);

        $this->actingAs($stagiaire)->post('/reports', [
            'semaine' => 3,
            'contenu' => 'Contenu du rapport.',
        ]);

        $notification = $mentor->notifications()->first();
        $this->assertNotNull($notification);

        $response = $this->actingAs($mentor)->delete("/notifications/{$notification->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_un_utilisateur_ne_peut_pas_supprimer_la_notification_dun_autre(): void
    {
        Storage::fake('local');

        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        Stage::create([
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
        ]);

        $this->actingAs($stagiaire)->post('/reports', [
            'semaine' => 3,
            'contenu' => 'Contenu du rapport.',
        ]);

        $notification = $mentor->notifications()->first();
        $intrus = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($intrus)->delete("/notifications/{$notification->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
    }

    public function test_un_utilisateur_peut_supprimer_plusieurs_notifications_en_une_fois(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin2 = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/users', [
            'name' => 'Membre 1', 'email' => 'membre1@example.com', 'password' => 'motdepasse123', 'role' => 'stagiaire',
        ]);
        $this->actingAs($admin)->post('/users', [
            'name' => 'Membre 2', 'email' => 'membre2@example.com', 'password' => 'motdepasse123', 'role' => 'stagiaire',
        ]);

        $ids = $admin2->notifications()->pluck('id');
        $this->assertCount(2, $ids);

        $response = $this->actingAs($admin2)->delete('/notifications', ['ids' => $ids->toArray()]);

        $response->assertRedirect();
        $this->assertSame(0, $admin2->notifications()->count());
        $this->assertSame(2, $admin->notifications()->count());
    }

    public function test_la_suppression_groupee_ne_supprime_pas_les_notifications_dun_autre_utilisateur(): void
    {
        Storage::fake('local');

        $mentorA = User::factory()->create(['role' => 'mentor']);
        $stagiaireA = User::factory()->create(['role' => 'stagiaire']);
        Stage::create([
            'stagiaire_id' => $stagiaireA->id,
            'mentor_id' => $mentorA->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
        ]);
        $this->actingAs($stagiaireA)->post('/reports', ['semaine' => 3, 'contenu' => 'Contenu.']);
        $notificationDeA = $mentorA->notifications()->first();

        $intrus = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($intrus)->delete('/notifications', ['ids' => [$notificationDeA->id]]);

        $response->assertRedirect();
        $this->assertDatabaseHas('notifications', ['id' => $notificationDeA->id]);
    }
}
