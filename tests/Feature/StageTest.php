<?php

namespace Tests\Feature;

use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StageTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_administrateur_peut_creer_un_stage(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        $response = $this->actingAs($admin)->post('/stages', [
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-09-01',
            'date_fin' => '2026-11-01',
            'mode_travail' => 'presentiel',
        ]);

        $response->assertRedirect('/stages');
        $this->assertDatabaseHas('stages', [
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
        ]);
    }

    public function test_un_stagiaire_deja_affecte_ne_peut_pas_recevoir_un_second_stage_actif(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        Stage::create([
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-03-01',
        ]);

        $response = $this->actingAs($admin)->post('/stages', [
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-09-01',
            'date_fin' => '2026-11-01',
            'mode_travail' => 'presentiel',
        ]);

        $response->assertSessionHasErrors('stagiaire_id');
    }

    public function test_un_mentor_ne_peut_pas_creer_un_stage(): void
    {
        $mentorConnecte = User::factory()->create(['role' => 'mentor']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        $response = $this->actingAs($mentorConnecte)->post('/stages', [
            'stagiaire_id' => $stagiaire->id,
            'mentor_id' => $mentor->id,
            'date_debut' => '2026-09-01',
            'date_fin' => '2026-11-01',
            'mode_travail' => 'presentiel',
        ]);

        $response->assertForbidden();
    }
}
