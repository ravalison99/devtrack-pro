<?php

namespace Tests\Feature;

use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_stagiaire_peut_creer_une_entree_de_journal(): void
    {
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        $response = $this->actingAs($stagiaire)->post('/journal', [
            'date' => '2026-08-25',
            'contenu' => 'Première journée.',
        ]);

        $response->assertRedirect('/journal');
        $this->assertDatabaseHas('journal_entries', [
            'stagiaire_id' => $stagiaire->id,
            'contenu' => 'Première journée.',
        ]);
    }

    public function test_une_deuxieme_soumission_le_meme_jour_met_a_jour_au_lieu_de_dupliquer(): void
    {
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);

        $this->actingAs($stagiaire)->post('/journal', [
            'date' => '2026-08-25',
            'contenu' => 'Premier texte.',
        ]);

        $this->actingAs($stagiaire)->post('/journal', [
            'date' => '2026-08-25',
            'contenu' => 'Texte corrigé.',
        ]);

        $this->assertDatabaseCount('journal_entries', 1);
        $this->assertDatabaseHas('journal_entries', [
            'stagiaire_id' => $stagiaire->id,
            'contenu' => 'Texte corrigé.',
        ]);
    }

    public function test_un_stagiaire_ne_voit_que_ses_propres_entrees(): void
    {
        $stagiaire1 = User::factory()->create(['role' => 'stagiaire']);
        $stagiaire2 = User::factory()->create(['role' => 'stagiaire']);

        JournalEntry::create(['stagiaire_id' => $stagiaire1->id, 'date' => '2026-08-25', 'contenu' => 'Entrée du stagiaire 1']);
        JournalEntry::create(['stagiaire_id' => $stagiaire2->id, 'date' => '2026-08-25', 'contenu' => 'Entrée du stagiaire 2']);

        $response = $this->actingAs($stagiaire1)->get('/journal');

        $response->assertSee('Entrée du stagiaire 1');
        $response->assertDontSee('Entrée du stagiaire 2');
    }
}
