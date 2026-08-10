<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_utilisateur_peut_se_connecter_avec_des_identifiants_valides(): void
    {
        $user = User::factory()->create([
            'email' => 'test@devtrack.com',
            'password' => 'password123',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@devtrack.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_un_utilisateur_ne_peut_pas_se_connecter_avec_un_mauvais_mot_de_passe(): void
    {
        User::factory()->create([
            'email' => 'test@devtrack.com',
            'password' => 'password123',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@devtrack.com',
            'password' => 'mauvais_mot_de_passe',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_un_utilisateur_desactive_ne_peut_pas_se_connecter(): void
    {
        User::factory()->create([
            'email' => 'inactif@devtrack.com',
            'password' => 'password123',
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'inactif@devtrack.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
