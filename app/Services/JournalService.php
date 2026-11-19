<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\User;
use App\Repositories\Contracts\JournalRepositoryInterface;
use Carbon\CarbonImmutable;

class JournalService
{
    public function __construct(protected JournalRepositoryInterface $entries) {}

    public function enregistrer(User $stagiaire, string $date, string $contenu): JournalEntry
    {
        $entreeExistante = $this->entries->findByStagiaireAndDate($stagiaire->id, $date);

        if ($entreeExistante !== null) {
            return $this->entries->update($entreeExistante, ['contenu' => $contenu]);
        }

        return $this->entries->create([
            'stagiaire_id' => $stagiaire->id,
            'date' => $date,
            'contenu' => $contenu,
        ]);
    }

    /**
     * La semaine en cours (lundi → dimanche) : chaque jour indique s'il est rempli, manquant, futur ou aujourd'hui.
     * Le week-end n'est jamais « manquant » : on n'écrit pas son journal les jours non travaillés.
     *
     * @return array{jours: array<int, array<string, mixed>>, serie: int, rempli_aujourdhui: bool}
     */
    public function semaine(User $stagiaire, ?CarbonImmutable $aujourdhui = null): array
    {
        $aujourdhui = ($aujourdhui ?? CarbonImmutable::today())->startOfDay();
        $lundi = $aujourdhui->startOfWeek(CarbonImmutable::MONDAY);

        $remplies = array_flip($this->entries->datesRemplies($stagiaire->id, $aujourdhui->subDays(120)->toDateString()));

        $noms = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        $jours = [];

        for ($i = 0; $i < 7; $i++) {
            $jour = $lundi->addDays($i);
            $cle = $jour->toDateString();
            $rempli = isset($remplies[$cle]);

            $jours[] = [
                'date' => $cle,
                'nom' => $noms[$i],
                'numero' => $jour->day,
                'rempli' => $rempli,
                'aujourdhui' => $jour->isSameDay($aujourdhui),
                'futur' => $jour->gt($aujourdhui),
                'manquant' => ! $rempli && $jour->lt($aujourdhui) && ! $jour->isWeekend(),
            ];
        }

        return [
            'jours' => $jours,
            'serie' => $this->serie($remplies, $aujourdhui),
            'rempli_aujourdhui' => isset($remplies[$aujourdhui->toDateString()]),
        ];
    }

    /**
     * Nombre de jours ouvrés consécutifs avec une entrée, en comptant jusqu'à aujourd'hui.
     * Si aujourd'hui n'est pas encore rempli, la série n'est pas rompue (la journée n'est pas finie).
     *
     * @param  array<string, int>  $remplies
     */
    protected function serie(array $remplies, CarbonImmutable $aujourdhui): int
    {
        $serie = 0;

        for ($jour = $aujourdhui; $jour->gte($aujourdhui->subDays(120)); $jour = $jour->subDay()) {
            if ($jour->isWeekend()) {
                continue;
            }

            if (isset($remplies[$jour->toDateString()])) {
                $serie++;
            } elseif (! $jour->isSameDay($aujourdhui)) {
                break;
            }
        }

        return $serie;
    }
}
