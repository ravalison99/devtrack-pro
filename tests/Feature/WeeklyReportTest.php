<?php

namespace Tests\Feature;

use App\Models\Stage;
use App\Models\User;
use App\Models\WeeklyReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WeeklyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_stagiaire_peut_soumettre_un_rapport(): void
    {
        Storage::fake('local');

        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        $response = $this->actingAs($stagiaire)->post('/reports', [
            'semaine' => 5,
            'contenu' => 'Contenu du rapport de la semaine 5.',
        ]);

        $response->assertRedirect('/reports');
        $this->assertDatabaseHas('weekly_reports', [
            'stagiaire_id' => $stagiaire->id,
            'semaine' => 5,
        ]);
    }

    public function test_une_deuxieme_soumission_pour_la_meme_semaine_met_a_jour_au_lieu_de_dupliquer(): void
    {
        Storage::fake('local');

        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        $this->actingAs($stagiaire)->post('/reports', [
            'semaine' => 5,
            'contenu' => 'Premier contenu.',
        ]);

        $this->actingAs($stagiaire)->post('/reports', [
            'semaine' => 5,
            'contenu' => 'Contenu corrigé.',
        ]);

        $this->assertDatabaseCount('weekly_reports', 1);
        $this->assertDatabaseHas('weekly_reports', [
            'stagiaire_id' => $stagiaire->id,
            'contenu' => 'Contenu corrigé.',
        ]);
    }

    public function test_un_fichier_pdf_est_genere_lors_de_la_soumission(): void
    {
        Storage::fake('local');

        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        $this->actingAs($stagiaire)->post('/reports', [
            'semaine' => 5,
            'contenu' => 'Contenu du rapport.',
        ]);

        $report = WeeklyReport::first();

        $this->assertNotNull($report->fichier_pdf);
        Storage::disk('local')->assertExists($report->fichier_pdf);
    }

    public function test_le_mentor_du_stagiaire_peut_telecharger_le_rapport(): void
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
            'semaine' => 5,
            'contenu' => 'Contenu du rapport.',
        ]);

        $report = WeeklyReport::first();

        $response = $this->actingAs($mentor)->get("/reports/{$report->id}/download");

        $response->assertOk();
    }

    public function test_un_mentor_non_lie_ne_peut_pas_telecharger_le_rapport(): void
    {
        Storage::fake('local');

        $mentorNonLie = User::factory()->create(['role' => 'mentor']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        $this->actingAs($stagiaire)->post('/reports', [
            'semaine' => 5,
            'contenu' => 'Contenu du rapport.',
        ]);

        $report = WeeklyReport::first();

        $response = $this->actingAs($mentorNonLie)->get("/reports/{$report->id}/download");

        $response->assertForbidden();
    }
}
