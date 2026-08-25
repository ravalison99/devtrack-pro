<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\JournalRepositoryInterface;
use App\Services\JournalService;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    public function __construct(
        protected JournalService $journalService,
        protected JournalRepositoryInterface $entries
    ) {}

    public function index()
    {
        $entries = $this->entries->findByStagiaire(auth()->id());
        return view('journal.index', compact('entries'));
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
