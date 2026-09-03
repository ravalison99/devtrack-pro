<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_creation_d_un_stage_est_tracee_dans_le_journal_d_audit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        $this->actingAs($admin);

        $stage = Stage::create([
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'utilisateur_id' => $admin->id,
            'action' => 'created',
            'modele' => Stage::class,
            'modele_id' => $stage->id,
        ]);
    }

    public function test_apres_cinq_echecs_de_connexion_la_sixieme_tentative_est_bloquee(): void
    {
        $user = User::factory()->create(['email' => 'securite@test.com', 'password' => 'motdepasse123']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'securite@test.com',
                'password' => 'mauvais_mot_de_passe',
            ]);
        }

        $response = $this->post('/login', [
            'email' => 'securite@test.com',
            'password' => 'mauvais_mot_de_passe',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Trop de tentatives',
            session('errors')->first('email')
        );
    }

    public function test_une_connexion_reussie_reinitialise_le_compteur_de_tentatives(): void
    {
        $user = User::factory()->create(['email' => 'securite2@test.com', 'password' => 'motdepasse123']);

        $this->post('/login', [
            'email' => 'securite2@test.com',
            'password' => 'mauvais_mot_de_passe',
        ]);

        $response = $this->post('/login', [
            'email' => 'securite2@test.com',
            'password' => 'motdepasse123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertFalse(RateLimiter::tooManyAttempts('login:127.0.0.1', 5));
    }
}
