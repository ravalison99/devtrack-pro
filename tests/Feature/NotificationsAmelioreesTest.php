<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Groupe;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\DocumentDeposedNotification;
use App\Notifications\FileRemovedNotification;
use App\Notifications\GroupeCreatedNotification;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskAttachmentAddedNotification;
use App\Notifications\TaskCommentedNotification;
use App\Notifications\TaskOverdueNotification;
use App\Notifications\TaskStatusChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationsAmelioreesTest extends TestCase
{
    use RefreshDatabase;

    protected User $mentor;

    protected User $chef;

    protected User $assigne;

    protected User $autreMembre;

    protected Project $projet;

    protected Task $tache;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->mentor = User::factory()->create(['role' => 'mentor', 'name' => 'Mentor Martin']);
        $this->chef = User::factory()->create(['role' => 'stagiaire', 'name' => 'Chef Camille']);
        $this->assigne = User::factory()->create(['role' => 'stagiaire', 'name' => 'Bruno Assigné']);
        $this->autreMembre = User::factory()->create(['role' => 'stagiaire', 'name' => 'Clara Autre']);

        $this->projet = Project::create(['mentor_id' => $this->mentor->id, 'nom' => 'Projet Alpha']);
        Groupe::create(['nom' => 'Groupe Alpha', 'mentor_id' => $this->mentor->id, 'chef_id' => $this->chef->id, 'project_id' => $this->projet->id])
            ->membres()->attach([$this->chef->id, $this->assigne->id, $this->autreMembre->id]);

        $this->tache = Task::create(['project_id' => $this->projet->id, 'assigne_id' => $this->assigne->id, 'titre' => 'Rédiger le module']);
    }

    protected function notifier(User $user, array $data, ?string $luLe = null, ?string $creeLe = null): DatabaseNotification
    {
        $notification = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\TaskAssignedNotification',
            'data' => $data,
            'read_at' => $luLe,
        ]);
        if ($creeLe) {
            $notification->forceFill(['created_at' => $creeLe])->save();
        }

        return $notification;
    }

    protected function canaux(User $destinataire, string $classe): array
    {
        $canaux = [];
        Notification::assertSentTo($destinataire, $classe, function ($notification, $channels) use (&$canaux) {
            $canaux = $channels;

            return true;
        });

        return $canaux;
    }

    // ---- Page des notifications ------------------------------------------------------

    public function test_ouvrir_une_notification_la_marque_comme_lue_et_mene_a_la_page_concernee(): void
    {
        $notification = $this->notifier($this->assigne, ['message' => 'Nouvelle tâche', 'url' => "/tasks/{$this->tache->id}", 'icone' => 'bi-list-check']);

        $this->actingAs($this->assigne)->get("/notifications/{$notification->id}/open")
            ->assertRedirect("/tasks/{$this->tache->id}");

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_une_notification_ne_peut_etre_ouverte_ni_supprimee_que_par_son_destinataire(): void
    {
        $notification = $this->notifier($this->assigne, ['message' => 'Privé', 'url' => '/tasks/1']);

        $this->actingAs($this->chef)->get("/notifications/{$notification->id}/open")->assertForbidden();
        $this->actingAs($this->chef)->delete("/notifications/{$notification->id}")->assertForbidden();

        $this->assertNull($notification->fresh()->read_at);
        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
    }

    public function test_les_liens_externes_ou_absents_ne_sont_pas_suivis(): void
    {
        foreach (['https://site-malveillant.example', '//site-malveillant.example', 'javascript:alert(1)', null] as $url) {
            $notification = $this->notifier($this->assigne, ['message' => 'Test', 'url' => $url]);

            $this->actingAs($this->assigne)->get("/notifications/{$notification->id}/open")
                ->assertRedirect('/notifications');
        }
    }

    public function test_tout_marquer_comme_lu_ne_touche_que_mes_notifications(): void
    {
        $miennes = [$this->notifier($this->assigne, ['message' => 'A']), $this->notifier($this->assigne, ['message' => 'B'])];
        $autre = $this->notifier($this->chef, ['message' => 'C']);

        $this->actingAs($this->assigne)->post('/notifications/read-all')->assertRedirect();

        foreach ($miennes as $notification) {
            $this->assertNotNull($notification->fresh()->read_at);
        }
        $this->assertNull($autre->fresh()->read_at);
    }

    public function test_le_filtre_non_lues_et_le_message_s_affichent(): void
    {
        $this->notifier($this->assigne, ['message' => 'Message lu', 'icone' => 'bi-bell'], now());
        $this->notifier($this->assigne, ['message' => 'Message non lu', 'icone' => 'bi-bell']);

        $this->actingAs($this->assigne)->get('/notifications')
            ->assertOk()->assertSee('Message lu')->assertSee('Message non lu')->assertSee('Tout marquer comme lu');

        $this->actingAs($this->assigne)->get('/notifications?filtre=non_lues')
            ->assertOk()->assertSee('Message non lu')->assertDontSee('Message lu');
    }

    public function test_une_ancienne_notification_sans_message_ne_fait_pas_planter_la_page(): void
    {
        $this->notifier($this->assigne, ['titre' => 'Ancienne tâche']);
        $this->notifier($this->assigne, []);

        $this->actingAs($this->assigne)->get('/notifications')->assertOk()->assertSee('Ancienne tâche')->assertSee('Notification');
    }

    public function test_la_pastille_du_menu_indique_le_nombre_de_notifications_non_lues(): void
    {
        $this->notifier($this->assigne, ['message' => 'A']);
        $this->notifier($this->assigne, ['message' => 'B']);
        $this->notifier($this->assigne, ['message' => 'C'], now());

        $this->actingAs($this->assigne)->get('/dashboard')->assertSee('badge rounded-pill bg-danger ms-1">2<', false);
        $this->actingAs($this->chef)->get('/dashboard')->assertDontSee('badge rounded-pill bg-danger ms-1', false);
    }

    // ---- Canaux, file d'attente, messages --------------------------------------------

    public function test_les_notifications_sont_mises_en_file_et_l_in_app_reste_immediate(): void
    {
        $notification = new TaskCommentedNotification(
            $this->tache->comments()->create(['utilisateur_id' => $this->chef->id, 'contenu' => 'Salut'])
        );

        $this->assertInstanceOf(ShouldQueue::class, $notification);
        $this->assertSame('sync', $notification->viaConnections()['database']);
        $this->assertSame(config('queue.default'), $notification->viaConnections()['mail']);
    }

    public function test_chaque_notification_fournit_message_lien_et_icone(): void
    {
        $commentaire = $this->tache->comments()->create(['utilisateur_id' => $this->chef->id, 'contenu' => 'Salut']);

        $donnees = (new TaskCommentedNotification($commentaire))->toArray($this->assigne);

        $this->assertSame('Chef Camille a commenté la tâche « Rédiger le module ».', $donnees['message']);
        $this->assertSame("/tasks/{$this->tache->id}", $donnees['url']);
        $this->assertSame('bi-chat-left-text', $donnees['icone']);
    }

    public function test_le_mail_est_reserve_aux_evenements_importants(): void
    {
        $this->assertSame(['database', 'mail'], (new TaskAssignedNotification($this->tache))->via($this->assigne));
        $this->assertSame(['database', 'mail'], (new TaskStatusChangedNotification($this->tache, 'en_cours', 'en_revue'))->via($this->assigne));
        $this->assertSame(['database', 'mail'], (new TaskStatusChangedNotification($this->tache, 'en_revue', 'termine'))->via($this->assigne));
        // Un simple passage « à faire » → « en cours » reste dans l'application.
        $this->assertSame(['database'], (new TaskStatusChangedNotification($this->tache, 'a_faire', 'en_cours'))->via($this->assigne));

        $commentaire = $this->tache->comments()->create(['utilisateur_id' => $this->chef->id, 'contenu' => 'Salut']);
        $this->assertSame(['database'], (new TaskCommentedNotification($commentaire))->via($this->assigne));
    }

    public function test_le_mail_contient_le_lien_vers_la_page_concernee(): void
    {
        $mail = (new TaskAssignedNotification($this->tache))->toMail($this->assigne);

        $this->assertSame(url("/tasks/{$this->tache->id}"), $mail->actionUrl);
        $this->assertStringContainsString('Rédiger le module', implode(' ', $mail->introLines));
    }

    public function test_une_vraie_notification_est_enregistree_avec_son_lien_meme_avec_la_file_synchrone(): void
    {
        $this->actingAs($this->chef)->post("/tasks/{$this->tache->id}/comments", ['contenu' => 'Un point sur l\'avancement ?'])->assertRedirect();

        $notification = $this->assigne->notifications()->firstOrFail();
        $this->assertSame(TaskCommentedNotification::class, $notification->type);
        $this->assertSame("/tasks/{$this->tache->id}", $notification->data['url']);
    }

    // ---- Qui est prévenu, de quoi ----------------------------------------------------

    public function test_un_commentaire_previent_les_autres_participants_mais_pas_son_auteur(): void
    {
        Notification::fake();

        $this->actingAs($this->assigne)->post("/tasks/{$this->tache->id}/comments", ['contenu' => 'Voici ma réponse']);

        Notification::assertSentTo([$this->chef, $this->mentor], TaskCommentedNotification::class);
        Notification::assertNotSentTo($this->assigne, TaskCommentedNotification::class);
        Notification::assertNotSentTo($this->autreMembre, TaskCommentedNotification::class);
    }

    public function test_un_commentaire_du_mentor_previent_l_assigne_et_le_chef(): void
    {
        Notification::fake();

        $this->actingAs($this->mentor)->post("/tasks/{$this->tache->id}/comments", ['contenu' => 'À corriger']);

        Notification::assertSentTo([$this->assigne, $this->chef], TaskCommentedNotification::class);
        Notification::assertNotSentTo($this->mentor, TaskCommentedNotification::class);
    }

    public function test_un_fichier_rendu_sur_une_tache_previent_le_chef_et_le_mentor(): void
    {
        Notification::fake();

        $this->actingAs($this->assigne)->post("/tasks/{$this->tache->id}/attachments", [
            'fichier' => UploadedFile::fake()->create('rendu.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        Notification::assertSentTo([$this->chef, $this->mentor], TaskAttachmentAddedNotification::class);
        Notification::assertNotSentTo($this->assigne, TaskAttachmentAddedNotification::class);
    }

    public function test_le_depot_d_un_document_previent_le_mentor_par_mail_et_le_groupe_dans_l_application(): void
    {
        Notification::fake();

        $this->actingAs($this->chef)->post("/projects/{$this->projet->id}/documents", [
            'titre' => 'Rapport semaine 1',
            'categorie' => 'Compte rendu',
            'type_rapport' => 'hebdomadaire',
            'fichier' => UploadedFile::fake()->create('rapport.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $this->assertContains('mail', $this->canaux($this->mentor, DocumentDeposedNotification::class));
        $this->assertSame(['database'], $this->canaux($this->assigne, DocumentDeposedNotification::class));
        Notification::assertSentTo($this->autreMembre, DocumentDeposedNotification::class);
        Notification::assertNotSentTo($this->chef, DocumentDeposedNotification::class);
    }

    public function test_la_creation_d_un_groupe_previent_chaque_stagiaire(): void
    {
        Notification::fake();

        $mentor = User::factory()->create(['role' => 'mentor']);
        [$a, $b] = User::factory()->count(2)->create(['role' => 'stagiaire']);
        $projet = Project::create(['mentor_id' => $mentor->id, 'nom' => 'Projet Neuf']);

        $this->actingAs($mentor)->post('/groupes', [
            'nom' => 'Groupe Neuf',
            'membres' => [$a->id, $b->id],
            'chef_id' => $a->id,
            'project_id' => $projet->id,
        ])->assertRedirect('/groupes');

        Notification::assertSentTo([$a, $b], GroupeCreatedNotification::class);
        Notification::assertNotSentTo($mentor, GroupeCreatedNotification::class);
        $this->assertContains('mail', $this->canaux($a, GroupeCreatedNotification::class));

        $groupe = Groupe::where('nom', 'Groupe Neuf')->firstOrFail();
        $messageChef = (new GroupeCreatedNotification($groupe, true))->toArray($a)['message'];
        $messageMembre = (new GroupeCreatedNotification($groupe, false))->toArray($b)['message'];
        $this->assertStringContainsString('Vous en êtes le chef', $messageChef);
        $this->assertStringContainsString('Chef de groupe : '.$a->name, $messageMembre);
    }

    public function test_le_retrait_d_un_fichier_par_le_chef_previent_le_mentor(): void
    {
        $this->actingAs($this->chef)->post("/projects/{$this->projet->id}/documents", [
            'titre' => 'Rapport semaine 1',
            'categorie' => 'Compte rendu',
            'type_rapport' => 'hebdomadaire',
            'fichier' => UploadedFile::fake()->create('rapport.pdf', 100, 'application/pdf'),
        ]);
        $document = Document::firstOrFail();
        $version = $document->versions()->firstOrFail();

        Notification::fake();
        $this->actingAs($this->chef)->delete("/documents/{$document->id}/versions/{$version->id}")->assertRedirect();

        Notification::assertSentTo($this->mentor, FileRemovedNotification::class);
        Notification::assertNotSentTo($this->chef, FileRemovedNotification::class);
        Notification::assertNotSentTo($this->assigne, FileRemovedNotification::class);
    }

    public function test_celui_qui_change_le_statut_n_est_pas_notifie_de_sa_propre_action(): void
    {
        Notification::fake();

        $this->actingAs($this->assigne)->patch("/tasks/{$this->tache->id}/status", ['statut' => 'en_cours'])->assertRedirect();

        Notification::assertSentTo([$this->mentor, $this->chef], TaskStatusChangedNotification::class);
        Notification::assertNotSentTo($this->assigne, TaskStatusChangedNotification::class);
        // « en cours » : pas de mail, seulement la notification dans l'application.
        $this->assertSame(['database'], $this->canaux($this->mentor, TaskStatusChangedNotification::class));
    }

    // ---- Rappels et purge ------------------------------------------------------------

    public function test_le_rappel_previent_l_assigne_par_mail_et_le_chef_et_le_mentor_dans_l_application(): void
    {
        Notification::fake();
        $this->tache->update(['date_echeance' => now()->subDays(2)]);
        $terminee = Task::create(['project_id' => $this->projet->id, 'assigne_id' => $this->assigne->id, 'titre' => 'Finie', 'statut' => 'termine', 'date_echeance' => now()->subDays(5)]);
        Task::create(['project_id' => $this->projet->id, 'assigne_id' => $this->assigne->id, 'titre' => 'Future', 'date_echeance' => now()->addDays(5)]);

        $this->artisan('taches:rappel-retard')->assertSuccessful();

        Notification::assertSentTo([$this->assigne, $this->chef, $this->mentor], TaskOverdueNotification::class);
        Notification::assertNotSentTo($this->autreMembre, TaskOverdueNotification::class);
        Notification::assertSentToTimes($this->assigne, TaskOverdueNotification::class, 1);
        $this->assertContains('mail', $this->canaux($this->assigne, TaskOverdueNotification::class));
        $this->assertSame(['database'], $this->canaux($this->mentor, TaskOverdueNotification::class));
        $this->assertNotNull($terminee);
    }

    public function test_le_rappel_n_est_pas_renvoye_chaque_jour(): void
    {
        $this->tache->update(['date_echeance' => now()->subDays(2)]);

        $this->artisan('taches:rappel-retard')->assertSuccessful();
        $this->assertSame(1, $this->assigne->notifications()->where('type', TaskOverdueNotification::class)->count());

        // Lendemain : déjà rappelé il y a moins de 3 jours.
        $this->artisan('taches:rappel-retard')->assertSuccessful();
        $this->assertSame(1, $this->assigne->notifications()->where('type', TaskOverdueNotification::class)->count());

        // 4 jours plus tard : nouveau rappel.
        $this->assigne->notifications()->update(['created_at' => now()->subDays(4)]);
        $this->artisan('taches:rappel-retard')->assertSuccessful();
        $this->assertSame(2, $this->assigne->notifications()->where('type', TaskOverdueNotification::class)->count());
    }

    public function test_la_purge_supprime_les_anciennes_notifications_lues_et_garde_le_reste(): void
    {
        $luesAnciennes = $this->notifier($this->assigne, ['message' => 'Vieille lue'], now()->subDays(45));
        $luesRecentes = $this->notifier($this->assigne, ['message' => 'Récente lue'], now()->subDays(5));
        $nonLueRecente = $this->notifier($this->assigne, ['message' => 'Non lue récente']);
        $nonLueTresAncienne = $this->notifier($this->assigne, ['message' => 'Non lue ancienne'], null, now()->subDays(200)->toDateTimeString());

        $this->artisan('notifications:purger')->assertSuccessful();

        $this->assertDatabaseMissing('notifications', ['id' => $luesAnciennes->id]);
        $this->assertDatabaseMissing('notifications', ['id' => $nonLueTresAncienne->id]);
        $this->assertDatabaseHas('notifications', ['id' => $luesRecentes->id]);
        $this->assertDatabaseHas('notifications', ['id' => $nonLueRecente->id]);
    }

    public function test_la_planification_inclut_rappels_et_purge(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('taches:rappel-retard')->expectsOutputToContain('notifications:purger')->assertSuccessful();
    }
}
