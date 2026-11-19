<?php

namespace Tests\Feature;

use App\Models\Groupe;
use App\Models\JournalEntry;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\JournalService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Refonte de l'interface : accueil « Mon espace », tâches, journal, navigation, composants et mode sombre.
 */
class InterfaceStagiaireTest extends TestCase
{
    use RefreshDatabase;

    protected User $mentor;

    protected User $chef;

    protected User $membre;

    protected User $sansProjet;

    protected Project $projet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mentor = User::factory()->create(['role' => 'mentor', 'name' => 'Mentor Martin']);
        $this->chef = User::factory()->create(['role' => 'stagiaire', 'name' => 'Chef Camille']);
        $this->membre = User::factory()->create(['role' => 'stagiaire', 'name' => 'Bruno Membre']);
        $this->sansProjet = User::factory()->create(['role' => 'stagiaire', 'name' => 'Sam Libre']);

        $this->projet = Project::create(['mentor_id' => $this->mentor->id, 'nom' => 'Projet Alpha']);
        Groupe::create(['nom' => 'Groupe Alpha', 'mentor_id' => $this->mentor->id, 'chef_id' => $this->chef->id, 'project_id' => $this->projet->id])
            ->membres()->attach([$this->chef->id, $this->membre->id]);
    }

    protected function tache(string $titre, ?User $assigne, array $extra = []): Task
    {
        return Task::create(array_merge(['project_id' => $this->projet->id, 'assigne_id' => $assigne?->id, 'titre' => $titre], $extra));
    }

    // ---- Accueil « Mon espace » ------------------------------------------------------

    public function test_l_accueil_montre_projet_groupe_chef_et_mentor_sans_stage(): void
    {
        $this->travelTo(now()->setTime(10, 0)); // la salutation dépend de l'heure

        $this->actingAs($this->membre)->get('/dashboard')
            ->assertOk()
            ->assertSee('Bonjour', false)
            ->assertSee('Mon projet')
            ->assertSee('Projet Alpha')
            ->assertSee('Groupe « Groupe Alpha »', false)
            ->assertSee('Chef Camille')
            ->assertSee('Mentor Martin')
            // Défaut corrigé : un stagiaire de groupe n'a pas besoin d'un « stage actif ».
            ->assertDontSee('Aucun stage actif');
    }

    public function test_la_salutation_depend_de_l_heure(): void
    {
        $this->travelTo(now()->setTime(20, 30));
        $this->actingAs($this->membre)->get('/dashboard')->assertSee('Bonsoir', false);

        $this->travelTo(now()->setTime(9, 0));
        $this->actingAs($this->membre)->get('/dashboard')->assertSee('Bonjour', false);
    }

    public function test_l_accueil_liste_d_abord_les_taches_en_retard_puis_les_plus_proches(): void
    {
        $this->tache('Tache lointaine', $this->membre, ['date_echeance' => now()->addDays(20)]);
        $this->tache('Tache en retard', $this->membre, ['date_echeance' => now()->subDays(3)]);
        $this->tache('Tache demain', $this->membre, ['date_echeance' => now()->addDay()]);
        $this->tache('Tache sans date', $this->membre);
        $this->tache('Tache terminee', $this->membre, ['statut' => 'termine', 'date_echeance' => now()->subDays(9)]);
        $this->tache('Tache du chef', $this->chef, ['date_echeance' => now()]);

        $this->actingAs($this->membre)->get('/dashboard')
            ->assertSeeInOrder(['Tache en retard', 'Tache demain', 'Tache lointaine', 'Tache sans date'])
            ->assertSee('En retard de 3 j')
            ->assertSee('Demain')
            ->assertDontSee('Tache terminee')
            ->assertDontSee('Tache du chef');
    }

    public function test_les_etats_vides_de_l_accueil_sont_explicites(): void
    {
        $this->actingAs($this->sansProjet)->get('/dashboard')
            ->assertOk()
            ->assertSee('Pas encore de projet')
            ->assertSee('Rien d&#039;urgent pour le moment', false)
            ->assertSee('Rien de neuf')
            ->assertSee('Aucune tâche ne vous est encore confiée');
    }

    public function test_le_message_d_accueil_s_adapte_a_l_avancement(): void
    {
        $this->tache('A', $this->membre, ['statut' => 'termine']);
        $this->tache('B', $this->membre);

        $this->actingAs($this->membre)->get('/dashboard')->assertSee('Vous avez terminé 1 tâche sur 2', false);

        Task::where('titre', 'B')->update(['statut' => 'termine']);
        $this->actingAs($this->membre)->get('/dashboard')->assertSee('toutes vos tâches sont terminées', false);

        Task::where('titre', 'B')->update(['statut' => 'a_faire', 'date_echeance' => now()->subDay()]);
        $this->actingAs($this->membre)->get('/dashboard')->assertSee('1 tâche est en retard', false);
    }

    public function test_le_chef_voit_l_avancement_de_l_equipe_et_peut_creer_une_tache_pas_le_membre(): void
    {
        $this->tache('T1', $this->membre, ['statut' => 'termine']);
        $this->tache('T2', $this->chef);

        $this->actingAs($this->chef)->get('/dashboard')
            ->assertSee("Avancement de l'équipe", false)
            ->assertSee('Chef de groupe')
            ->assertSee(route('tasks.create'), false);

        $this->actingAs($this->membre)->get('/dashboard')
            ->assertDontSee("Avancement de l'équipe", false)
            ->assertDontSee(route('tasks.create'), false)
            ->assertSee('Membre du groupe');
    }

    public function test_le_bouton_du_journal_change_une_fois_l_entree_du_jour_ecrite(): void
    {
        $this->actingAs($this->membre)->get('/dashboard')->assertSee('Écrire mon journal du jour');

        JournalEntry::create(['stagiaire_id' => $this->membre->id, 'date' => today()->toDateString(), 'contenu' => 'Fait']);

        $this->actingAs($this->membre)->get('/dashboard')->assertDontSee('Écrire mon journal du jour')->assertSee('Mon journal');
    }

    public function test_l_activite_recente_reprend_les_notifications_et_ouvre_la_page_concernee(): void
    {
        $this->actingAs($this->chef)->post('/tasks', [
            'project_id' => $this->projet->id, 'assigne_id' => $this->membre->id, 'titre' => 'Nouvelle mission', 'priorite' => 'haute',
        ]);

        $notification = $this->membre->notifications()->firstOrFail();

        $this->actingAs($this->membre)->get('/dashboard')
            ->assertSee('Activité récente')
            ->assertSee('Nouvelle tâche « Nouvelle mission »', false)
            ->assertSee(route('notifications.open', $notification->id), false);
    }

    public function test_le_service_fournit_progression_et_mes_taches_distinctes_des_taches_visibles(): void
    {
        $this->tache('Terminee', $this->chef, ['statut' => 'termine']);
        $this->tache('Mienne', $this->chef);
        $this->tache('Du membre', $this->membre, ['date_echeance' => now()->subDay()]);

        $service = app(DashboardService::class);
        $chef = $service->indicateursPour($this->chef);

        // « Mes tâches » = celles du chef ; le projet entier = progression de l'équipe.
        $this->assertSame(2, $chef['progression']['total']);
        $this->assertSame(50, $chef['progression']['pourcentage']);
        $this->assertSame(3, $chef['progression_equipe']['total']);
        $this->assertSame(1, $chef['progression_equipe']['en_retard']);
        $this->assertTrue($chef['est_chef']);
        $this->assertSame(['Mienne'], $chef['mes_taches']->pluck('titre')->all());

        $membre = $service->indicateursPour($this->membre);
        $this->assertNull($membre['progression_equipe']);
        $this->assertFalse($membre['est_chef']);
        $this->assertSame($this->projet->id, $membre['projet']->id);
    }

    // ---- Tâches ----------------------------------------------------------------------

    public function test_la_liste_des_taches_affiche_des_libelles_en_francais_et_pas_les_valeurs_brutes(): void
    {
        $this->tache('Tache A', $this->membre, ['statut' => 'en_cours', 'priorite' => 'haute']);

        $page = $this->actingAs($this->membre)->get('/tasks');
        $page->assertOk()->assertSee('En cours')->assertSee('Haute')->assertSee('Mes tâches');
        $page->assertDontSee('>en_cours<', false)->assertDontSee('>haute<', false);
    }

    public function test_les_filtres_de_statut_de_retard_et_de_recherche_fonctionnent(): void
    {
        $this->tache('Alpha faire', $this->membre);
        $this->tache('Beta cours', $this->membre, ['statut' => 'en_cours']);
        $this->tache('Gamma retard', $this->membre, ['date_echeance' => now()->subDays(2)]);
        $this->tache('Delta fini en retard', $this->membre, ['statut' => 'termine', 'date_echeance' => now()->subDays(2)]);
        $this->tache('Epsilon du chef', $this->chef);

        $this->actingAs($this->membre)->get('/tasks?statut=en_cours')
            ->assertSee('Beta cours')->assertDontSee('Alpha faire')->assertDontSee('Gamma retard');

        $this->actingAs($this->membre)->get('/tasks?retard=1')
            ->assertSee('Gamma retard')->assertDontSee('Delta fini en retard')->assertDontSee('Alpha faire');

        $this->actingAs($this->membre)->get('/tasks?q=alpha')
            ->assertSee('Alpha faire')->assertDontSee('Beta cours');

        // Le filtre ne contourne pas la visibilité : la tâche du chef reste invisible pour le membre.
        $this->actingAs($this->membre)->get('/tasks')->assertDontSee('Epsilon du chef');
        $this->actingAs($this->chef)->get('/tasks?q=epsilon')->assertSee('Epsilon du chef');
    }

    public function test_une_liste_filtree_sans_resultat_propose_de_reinitialiser(): void
    {
        $this->actingAs($this->membre)->get('/tasks?statut=termine')
            ->assertSee('Aucune tâche ne correspond')
            ->assertSee('Voir toutes les tâches');

        $this->actingAs($this->membre)->get('/tasks')->assertSee('Aucune tâche pour le moment');
    }

    public function test_des_filtres_de_taches_invalides_sont_refuses(): void
    {
        $this->actingAs($this->membre)->get('/tasks?statut=inconnu')->assertSessionHasErrors('statut');
        $this->actingAs($this->membre)->get('/tasks?q='.str_repeat('a', 101))->assertSessionHasErrors('q');
    }

    public function test_le_detail_d_une_tache_montre_la_frise_les_actions_et_la_zone_de_depot(): void
    {
        $tache = $this->tache('Rédiger', $this->membre, ['statut' => 'en_cours', 'description' => 'Décrire le module en détail']);
        $tache->comments()->create(['utilisateur_id' => $this->membre->id, 'contenu' => 'Mon message']);
        $tache->comments()->create(['utilisateur_id' => $this->chef->id, 'contenu' => 'Réponse du chef']);

        $page = $this->actingAs($this->membre)->get("/tasks/{$tache->id}");

        $page->assertOk()
            ->assertSee('Décrire le module en détail')
            ->assertSee('Avancement de la tâche')
            ->assertSee('aria-current="step"', false)
            ->assertSee('Passer en « En revue »', false)
            ->assertSee('Revenir à « À faire »', false)
            // un stagiaire ne peut pas valider la tâche
            ->assertDontSee('Passer en « Terminé »', false)
            ->assertSee('Rendre mon travail')
            ->assertSee('data-dropzone', false);

        // Mes messages sont alignés à droite, ceux des autres à gauche.
        $this->assertSame(1, substr_count($page->getContent(), 'class="message moi"'));
        $this->assertSame(1, substr_count($page->getContent(), 'class="message "'));
    }

    public function test_les_boutons_de_statut_du_detail_changent_vraiment_le_statut(): void
    {
        $tache = $this->tache('Rédiger', $this->membre);

        $this->actingAs($this->membre)->patch("/tasks/{$tache->id}/status", ['statut' => 'en_cours'])->assertRedirect();

        $this->assertSame('en_cours', $tache->fresh()->statut);
    }

    public function test_le_kanban_utilise_les_nouvelles_cartes(): void
    {
        $this->tache('Carte kanban', $this->membre, ['priorite' => 'haute']);

        $this->actingAs($this->membre)->get("/projects/{$this->projet->id}/kanban")
            ->assertOk()
            ->assertSee('kanban-carte prio-haute', false)
            ->assertSee('Carte kanban')
            ->assertSee("Fil d'Ariane", false);
    }

    // ---- Journal ---------------------------------------------------------------------

    public function test_la_semaine_du_journal_marque_les_jours_remplis_manquants_et_le_week_end(): void
    {
        // Mercredi 18 novembre 2026.
        $mercredi = CarbonImmutable::parse('2026-11-18');
        JournalEntry::create(['stagiaire_id' => $this->membre->id, 'date' => '2026-11-16', 'contenu' => 'Lundi']);
        JournalEntry::create(['stagiaire_id' => $this->membre->id, 'date' => '2026-11-18', 'contenu' => 'Mercredi']);

        $semaine = app(JournalService::class)->semaine($this->membre, $mercredi);
        $jours = collect($semaine['jours'])->keyBy('nom');

        $this->assertCount(7, $semaine['jours']);
        $this->assertTrue($jours['Lun']['rempli']);
        $this->assertTrue($jours['Mar']['manquant']);
        $this->assertTrue($jours['Mer']['aujourdhui']);
        $this->assertTrue($semaine['rempli_aujourdhui']);
        $this->assertTrue($jours['Jeu']['futur']);
        $this->assertFalse($jours['Sam']['manquant'], 'Le week-end n\'est jamais manquant.');
    }

    public function test_la_serie_compte_les_jours_ouvres_consecutifs_en_ignorant_le_week_end(): void
    {
        foreach (['2026-11-12', '2026-11-13', '2026-11-16', '2026-11-17'] as $date) { // jeu, ven, lun, mar
            JournalEntry::create(['stagiaire_id' => $this->membre->id, 'date' => $date, 'contenu' => 'ok']);
        }
        $service = app(JournalService::class);

        // Mercredi, journée pas encore écrite : la série n'est pas rompue.
        $this->assertSame(4, $service->semaine($this->membre, CarbonImmutable::parse('2026-11-18'))['serie']);

        // Jeudi : mercredi manquant, la série est rompue.
        $this->assertSame(0, $service->semaine($this->membre, CarbonImmutable::parse('2026-11-19'))['serie']);

        // Mercredi rempli : la série passe à 5.
        JournalEntry::create(['stagiaire_id' => $this->membre->id, 'date' => '2026-11-18', 'contenu' => 'ok']);
        $this->assertSame(5, $service->semaine($this->membre, CarbonImmutable::parse('2026-11-18'))['serie']);
    }

    public function test_cliquer_un_jour_charge_son_entree_pour_la_modifier(): void
    {
        JournalEntry::create(['stagiaire_id' => $this->membre->id, 'date' => '2026-11-10', 'contenu' => 'Contenu à corriger']);

        $this->actingAs($this->membre)->get('/journal?date=2026-11-10')
            ->assertOk()
            ->assertSee("Modifier l'entrée du 10/11/2026", false)
            ->assertSee('Contenu à corriger')
            ->assertSee('Mettre à jour')
            ->assertSee("Revenir à aujourd'hui", false);

        $this->actingAs($this->membre)->get('/journal?date=pas-une-date')->assertSessionHasErrors('date');
    }

    public function test_l_historique_du_journal_est_une_frise_avec_dates_en_francais(): void
    {
        JournalEntry::create(['stagiaire_id' => $this->membre->id, 'date' => '2026-11-10', 'contenu' => 'Une journée']);

        $this->actingAs($this->membre)->get('/journal')
            ->assertSee('class="frise"', false)
            ->assertSee('Mardi 10 novembre 2026');
    }

    // ---- Navigation, thème, composants -----------------------------------------------

    public function test_le_menu_du_stagiaire_est_simplifie_et_mene_a_son_projet(): void
    {
        $page = $this->actingAs($this->membre)->get('/dashboard');

        $page->assertSee('Mon projet')->assertSee('Mes tâches')->assertSee('Accueil')->assertSee('Journal')
            ->assertSee('nav-mobile', false)
            ->assertSee('href="'.route('projects.show', $this->projet->id).'"', false)
            ->assertDontSee('Utilisateurs')->assertDontSee('Stages');

        $this->actingAs($this->sansProjet)->get('/dashboard')
            ->assertSee('href="'.route('projects.index').'"', false);
    }

    public function test_mentor_et_admin_gardent_leur_menu_sans_barre_basse(): void
    {
        $this->actingAs($this->mentor)->get('/dashboard')
            ->assertSee('Groupes')->assertSee('Stages')->assertSee('Projets')
            ->assertDontSee('nav-mobile', false)->assertDontSee('Mon projet');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/dashboard')->assertSee('Utilisateurs')->assertDontSee('nav-mobile', false);
    }

    public function test_le_theme_sombre_est_disponible_partout_et_applique_avant_l_affichage(): void
    {
        $page = $this->actingAs($this->membre)->get('/dashboard');

        $page->assertSee('data-bs-theme="light"', false)
            ->assertSee("localStorage.getItem('dtp-theme')", false)
            ->assertSee('prefers-color-scheme: dark', false)
            ->assertSee('data-theme-toggle', false)
            ->assertSee('css/devtrack.css', false)
            ->assertSee('js/devtrack.js', false)
            ->assertSee('Aller au contenu')
            ->assertSee('Déconnexion');

        auth()->logout();
        $this->get('/login')->assertOk()->assertSee('data-theme-toggle', false)->assertSee('connexion-hero', false)->assertSee('Se connecter');
    }

    public function test_la_feuille_de_style_definit_les_jetons_clair_et_sombre(): void
    {
        $css = file_get_contents(public_path('css/devtrack.css'));
        $js = file_get_contents(public_path('js/devtrack.js'));

        foreach (['--dtp-primary', '[data-bs-theme="dark"]', '.pastille-en_cours', '.anneau', '.nav-mobile', '.bulle', '.dropzone', '.semaine', 'prefers-reduced-motion'] as $attendu) {
            $this->assertStringContainsString($attendu, $css, "Le CSS doit contenir {$attendu}");
        }
        foreach (['dtp-theme', 'data-theme-toggle', 'data-dropzone', 'data-compte'] as $attendu) {
            $this->assertStringContainsString($attendu, $js, "Le JS doit contenir {$attendu}");
        }
    }

    public function test_un_message_de_succes_s_affiche_en_toast(): void
    {
        $this->actingAs($this->membre)->withSession(['success' => 'Bien enregistré'])->get('/dashboard')
            ->assertSee('toast-dtp', false)->assertSee('Bien enregistré')->assertSee('data-auto', false);
    }

    public function test_composants_pastilles_avatar_echeance_et_anneau(): void
    {
        $this->assertStringContainsString('pastille-en_revue', Blade::render('<x-badge-statut statut="en_revue" />'));
        $this->assertStringContainsString('En revue', Blade::render('<x-badge-statut statut="en_revue" />'));
        $this->assertStringContainsString('Haute', Blade::render('<x-badge-priorite priorite="haute" />'));

        $camille = Blade::render('<x-avatar :user="$u" />', ['u' => $this->chef]);
        $this->assertStringContainsString('>CC<', $camille);
        $this->assertSame($camille, Blade::render('<x-avatar :user="$u" />', ['u' => $this->chef]), 'Une même personne garde la même couleur.');
        $this->assertStringContainsString('avatar-inconnu', Blade::render('<x-avatar :user="null" />'));

        $this->assertStringContainsString('En retard de 2 j', Blade::render('<x-echeance :date="$d" statut="a_faire" />', ['d' => now()->subDays(2)]));
        $this->assertStringContainsString('Aujourd&#039;hui', Blade::render('<x-echeance :date="$d" statut="a_faire" />', ['d' => now()]));
        $this->assertStringContainsString('Demain', Blade::render('<x-echeance :date="$d" statut="a_faire" />', ['d' => now()->addDay()]));
        $this->assertStringNotContainsString('En retard', Blade::render('<x-echeance :date="$d" statut="termine" />', ['d' => now()->subDays(2)]));
        $this->assertStringContainsString('—', Blade::render('<x-echeance :date="null" />'));

        $this->assertStringContainsString('--p: 100', Blade::render('<x-anneau :pourcentage="250" />'));
        $this->assertStringContainsString('--p: 0', Blade::render('<x-anneau :pourcentage="-5" />'));
    }

    public function test_page_projet_avec_equipe_et_avancement_pour_le_stagiaire(): void
    {
        $this->tache('Une', $this->membre, ['statut' => 'termine']);
        $this->tache('Deux', $this->membre);

        $this->actingAs($this->membre)->get("/projects/{$this->projet->id}")
            ->assertOk()
            ->assertSee('Équipe et avancement')
            ->assertSee('Ma progression')
            ->assertSee('1 tâche terminée sur 2', false)
            ->assertSee('Chef Camille');

        $this->actingAs($this->chef)->get("/projects/{$this->projet->id}")->assertSee('Avancement du projet');
    }
}
