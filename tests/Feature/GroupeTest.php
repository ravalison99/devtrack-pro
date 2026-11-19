<?php

namespace Tests\Feature;

use App\Models\Groupe;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GroupeTest extends TestCase
{
    use RefreshDatabase;

    protected function mentor(): User
    {
        return User::factory()->create(['role' => 'mentor']);
    }

    protected function stagiaires(int $nombre): array
    {
        return User::factory()->count($nombre)->create(['role' => 'stagiaire'])->all();
    }

    protected function projet(User $mentor, string $nom = 'Projet A'): Project
    {
        return Project::create(['mentor_id' => $mentor->id, 'nom' => $nom]);
    }

    protected function payload(array $stagiaires, Project $projet, array $surcharge = []): array
    {
        return array_merge([
            'nom' => 'Groupe Alpha',
            'membres' => array_map(fn (User $u) => $u->id, $stagiaires),
            'chef_id' => $stagiaires[0]->id,
            'project_id' => $projet->id,
        ], $surcharge);
    }

    #[DataProvider('nombresDeMembres')]
    public function test_un_mentor_peut_creer_un_groupe_de_un_a_trois_stagiaires(int $nombre): void
    {
        $mentor = $this->mentor();
        $stagiaires = $this->stagiaires($nombre);
        $projet = $this->projet($mentor);

        $response = $this->actingAs($mentor)->post('/groupes', $this->payload($stagiaires, $projet));

        $response->assertRedirect('/groupes');
        $groupe = Groupe::firstOrFail();
        $this->assertSame($nombre, $groupe->membres()->count());
        $this->assertSame($stagiaires[0]->id, $groupe->chef_id);
        $this->assertSame($mentor->id, $groupe->mentor_id);
        // Association correcte du projet au groupe.
        $this->assertSame($projet->id, $groupe->project_id);
        $this->assertSame($groupe->id, $projet->fresh()->groupe->id);
    }

    public static function nombresDeMembres(): array
    {
        return ['un stagiaire' => [1], 'deux stagiaires' => [2], 'trois stagiaires' => [3]];
    }

    public function test_un_quatrieme_stagiaire_est_refuse(): void
    {
        $mentor = $this->mentor();
        $stagiaires = $this->stagiaires(4);

        $this->actingAs($mentor)
            ->post('/groupes', $this->payload($stagiaires, $this->projet($mentor)))
            ->assertSessionHasErrors('membres');

        $this->assertDatabaseCount('groupes', 0);
    }

    public function test_le_chef_doit_etre_membre_du_groupe(): void
    {
        $mentor = $this->mentor();
        [$a, $b, $externe] = $this->stagiaires(3);

        $this->actingAs($mentor)
            ->post('/groupes', $this->payload([$a, $b], $this->projet($mentor), ['chef_id' => $externe->id]))
            ->assertSessionHasErrors('chef_id');

        $this->assertDatabaseCount('groupes', 0);
    }

    public function test_le_chef_de_groupe_est_obligatoire_ainsi_que_le_nom_et_le_projet(): void
    {
        $mentor = $this->mentor();
        [$a] = $this->stagiaires(1);

        $this->actingAs($mentor)
            ->post('/groupes', ['membres' => [$a->id]])
            ->assertSessionHasErrors(['nom', 'chef_id', 'project_id']);
    }

    public function test_les_doublons_et_les_utilisateurs_non_stagiaires_sont_refuses(): void
    {
        $mentor = $this->mentor();
        [$a] = $this->stagiaires(1);
        $projet = $this->projet($mentor);

        $this->actingAs($mentor)
            ->post('/groupes', $this->payload([$a, $a], $projet))
            ->assertSessionHasErrors('membres.1');

        // Un mentor n'est pas un stagiaire, et un identifiant inexistant n'est pas accepté (pas de saisie libre).
        $this->actingAs($mentor)
            ->post('/groupes', $this->payload([$a], $projet, ['membres' => [$a->id, $mentor->id, 99999], 'chef_id' => $a->id]))
            ->assertSessionHasErrors(['membres.1', 'membres.2']);

        $this->assertDatabaseCount('groupes', 0);
    }

    public function test_un_stagiaire_ne_peut_appartenir_qu_a_un_seul_groupe(): void
    {
        $mentor = $this->mentor();
        [$a, $b] = $this->stagiaires(2);

        $this->actingAs($mentor)->post('/groupes', $this->payload([$a], $this->projet($mentor, 'P1')))->assertRedirect('/groupes');

        $this->actingAs($mentor)
            ->post('/groupes', $this->payload([$a, $b], $this->projet($mentor, 'P2'), ['nom' => 'Groupe Beta']))
            ->assertSessionHasErrors('membres.0');

        $this->assertDatabaseCount('groupes', 1);
    }

    public function test_un_projet_deja_affecte_ne_peut_pas_etre_reaffecte(): void
    {
        $mentor = $this->mentor();
        [$a, $b] = $this->stagiaires(2);
        $projet = $this->projet($mentor);

        $this->actingAs($mentor)->post('/groupes', $this->payload([$a], $projet))->assertRedirect('/groupes');

        $this->actingAs($mentor)
            ->post('/groupes', $this->payload([$b], $projet, ['nom' => 'Groupe Beta']))
            ->assertSessionHasErrors('project_id');

        $this->assertDatabaseCount('groupes', 1);
    }

    public function test_un_mentor_ne_peut_pas_affecter_le_projet_d_un_autre_mentor(): void
    {
        $mentor = $this->mentor();
        $autreMentor = $this->mentor();
        [$a] = $this->stagiaires(1);

        $this->actingAs($mentor)
            ->post('/groupes', $this->payload([$a], $this->projet($autreMentor)))
            ->assertSessionHasErrors('project_id');
    }

    public function test_seul_un_mentor_peut_gerer_les_groupes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$stagiaire] = $this->stagiaires(1);
        $projet = $this->projet($this->mentor());

        foreach ([$admin, $stagiaire] as $utilisateur) {
            $this->actingAs($utilisateur)->get('/groupes')->assertForbidden();
            $this->actingAs($utilisateur)->get('/groupes/create')->assertForbidden();
            $this->actingAs($utilisateur)->post('/groupes', $this->payload([$stagiaire], $projet))->assertForbidden();
        }

        $this->assertDatabaseCount('groupes', 0);
    }

    public function test_le_formulaire_ne_propose_que_les_stagiaires_libres_et_les_projets_non_affectes(): void
    {
        $mentor = $this->mentor();
        [$libre, $pris] = $this->stagiaires(2);
        $libre->update(['name' => 'Stagiaire Libre']);
        $pris->update(['name' => 'Stagiaire Pris']);
        $this->actingAs($mentor)->post('/groupes', $this->payload([$pris], $this->projet($mentor, 'Projet Affecté')));
        $this->projet($mentor, 'Projet Disponible');

        $this->actingAs($mentor)->get('/groupes/create')
            ->assertOk()
            ->assertSee('Stagiaire Libre')
            ->assertDontSee('Stagiaire Pris')
            ->assertSee('Projet Disponible')
            ->assertDontSee('Projet Affecté');
    }
}
