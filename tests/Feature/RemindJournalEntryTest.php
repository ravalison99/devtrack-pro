<?php

namespace Tests\Feature;

use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RemindJournalEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_seuls_les_stagiaires_sans_journal_du_jour_recoivent_un_rappel(): void
    {
        $retardataire = User::factory()->create(['role' => 'stagiaire', 'name' => 'Retardataire']);
        $assidu = User::factory()->create(['role' => 'stagiaire', 'name' => 'Assidu']);
        JournalEntry::create(['stagiaire_id' => $assidu->id, 'date' => now()->toDateString(), 'contenu' => 'Fait']);

        Log::spy();

        $this->artisan('journal:remind')
            ->expectsOutput('Rappels de journal envoyés.')
            ->assertSuccessful();

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(fn (string $message) => str_contains($message, $retardataire->name));
    }
}
