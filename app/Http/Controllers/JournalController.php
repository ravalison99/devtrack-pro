<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\JournalRepositoryInterface;
use App\Services\JournalService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    public function __construct(
        protected JournalService $journalService,
        protected JournalRepositoryInterface $entries
    ) {}

    public function index(Request $request)
    {
        $utilisateur = $request->user();

        // Un clic sur un jour de la semaine charge son entrée pour la lire ou la modifier.
        $request->validate(['date' => ['nullable', 'date']]);
        $dateChoisie = $request->filled('date')
            ? CarbonImmutable::parse($request->query('date'))->toDateString()
            : today()->toDateString();

        return view('journal.index', [
            'entries' => $this->entries->paginateByStagiaire($utilisateur->id, 6),
            'entreeChoisie' => $this->entries->findByStagiaireAndDate($utilisateur->id, $dateChoisie),
            'dateChoisie' => $dateChoisie,
            'semaine' => $this->journalService->semaine($utilisateur),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'contenu' => ['required', 'string', 'max:2000'],
        ]);

        $this->journalService->enregistrer(auth()->user(), $data['date'], $data['contenu']);

        return redirect()
            ->route('journal.index')
            ->with('success', 'Entrée enregistrée.');
    }
}
