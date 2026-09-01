<?php

namespace Tests\Feature;

use App\Models\Stage;
use App\Models\User;
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
}
