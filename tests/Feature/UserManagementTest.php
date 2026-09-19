<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_administrateur_peut_creer_un_administrateur(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Nouvel Admin',
            'email' => 'nouvel.admin@example.com',
            'password' => 'motdepasse123',
            'role' => 'admin',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'email' => 'nouvel.admin@example.com',
            'role' => 'admin',
        ]);
    }

    public function test_un_administrateur_peut_creer_un_mentor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Nouveau Mentor',
            'email' => 'nouveau.mentor@example.com',
            'password' => 'motdepasse123',
            'role' => 'mentor',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'email' => 'nouveau.mentor@example.com',
            'role' => 'mentor',
        ]);
    }

    public function test_un_administrateur_peut_creer_un_stagiaire(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Nouveau Stagiaire',
            'email' => 'nouveau.stagiaire@example.com',
            'password' => 'motdepasse123',
            'role' => 'stagiaire',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'email' => 'nouveau.stagiaire@example.com',
            'role' => 'stagiaire',
        ]);
    }

    public function test_le_mot_de_passe_est_stocke_hache(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/users', [
            'name' => 'Nouveau Stagiaire',
            'email' => 'hache@example.com',
            'password' => 'motdepasse123',
            'role' => 'stagiaire',
        ]);

        $utilisateur = User::where('email', 'hache@example.com')->first();

        $this->assertNotSame('motdepasse123', $utilisateur->password);
        $this->assertTrue(Hash::check('motdepasse123', $utilisateur->password));
    }

    public function test_un_mentor_ne_peut_pas_creer_un_utilisateur(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);

        $response = $this->actingAs($mentor)->post('/users', [
            'name' => 'Intrus',
            'email' => 'intrus@example.com',
            'password' => 'motdepasse123',
            'role' => 'stagiaire',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'intrus@example.com']);
    }

    public function test_un_stagiaire_ne_peut_pas_creer_un_utilisateur(): void
    {
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        $response = $this->actingAs($stagiaire)->post('/users', [
            'name' => 'Intrus',
            'email' => 'intrus2@example.com',
            'password' => 'motdepasse123',
            'role' => 'stagiaire',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'intrus2@example.com']);
    }

    public function test_un_email_deja_utilise_est_refuse(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['email' => 'deja.pris@example.com']);

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Doublon',
            'email' => 'deja.pris@example.com',
            'password' => 'motdepasse123',
            'role' => 'stagiaire',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 2);
    }

    public function test_un_administrateur_peut_voir_la_liste_des_utilisateurs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['name' => 'Jean Dupont', 'role' => 'stagiaire']);

        $response = $this->actingAs($admin)->get('/users');

        $response->assertOk();
        $response->assertSee('Jean Dupont');
    }

    public function test_un_mentor_ne_peut_pas_voir_la_liste_des_utilisateurs(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);

        $response = $this->actingAs($mentor)->get('/users');

        $response->assertForbidden();
    }

    public function test_le_tri_par_nom_croissant_fonctionne(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Zéro']);
        User::factory()->create(['name' => 'Zoé']);
        User::factory()->create(['name' => 'Alice']);

        $response = $this->actingAs($admin)->get('/users?sort=name&direction=asc');

        $response->assertOk();
        $contenu = $response->getContent();

        $this->assertLessThan(strpos($contenu, 'Zoé'), strpos($contenu, 'Alice'));
    }

    public function test_le_tri_par_nom_decroissant_fonctionne(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Zéro']);
        User::factory()->create(['name' => 'Zoé']);
        User::factory()->create(['name' => 'Alice']);

        $response = $this->actingAs($admin)->get('/users?sort=name&direction=desc');

        $response->assertOk();
        $contenu = $response->getContent();

        $this->assertLessThan(strpos($contenu, 'Alice'), strpos($contenu, 'Zoé'));
    }

    public function test_la_liste_des_utilisateurs_est_paginee_a_cinq_par_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(20)->create();

        $response = $this->actingAs($admin)->get('/users');

        $response->assertOk();
        $response->assertViewHas('utilisateurs', fn ($utilisateurs) => $utilisateurs->count() === 5);
    }

    public function test_la_recherche_filtre_les_utilisateurs_par_nom(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['name' => 'Jean Dupont']);
        User::factory()->create(['name' => 'Alice Martin']);

        $response = $this->actingAs($admin)->get('/users?search=Jean');

        $response->assertOk();
        $response->assertSee('Jean Dupont');
        $response->assertDontSee('Alice Martin');
    }

    public function test_la_recherche_ajax_retourne_un_fragment_html(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['name' => 'Jean Dupont']);

        $response = $this->actingAs($admin)->get('/users?search=Jean', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertSee('Jean Dupont');
        $response->assertDontSee('Créer un utilisateur');
    }

    public function test_un_admin_peut_modifier_un_utilisateur(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire', 'name' => 'Ancien Nom']);

        $response = $this->actingAs($admin)->put("/users/{$stagiaire->id}", [
            'name' => 'Nouveau Nom',
            'email' => $stagiaire->email,
            'role' => 'stagiaire',
            'is_active' => '1',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', ['id' => $stagiaire->id, 'name' => 'Nouveau Nom']);
    }

    public function test_un_admin_peut_desactiver_un_utilisateur(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire', 'is_active' => true]);

        $response = $this->actingAs($admin)->delete("/users/{$stagiaire->id}");

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', ['id' => $stagiaire->id, 'is_active' => false]);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_un_admin_ne_peut_pas_se_desactiver_lui_meme(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($admin)->delete("/users/{$admin->id}");

        $response->assertSessionHasErrors('utilisateur');
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_active' => true]);
    }

    public function test_un_mentor_ne_peut_pas_desactiver_un_utilisateur(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        $response = $this->actingAs($mentor)->delete("/users/{$stagiaire->id}");

        $response->assertForbidden();
    }
}
