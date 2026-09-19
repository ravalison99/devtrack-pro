<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStageRequest;
use App\Http\Requests\UpdateStageStatusRequest;
use App\Models\Stage;
use App\Repositories\Contracts\StageRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\StageService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class StageController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected StageService $stageService,
        protected StageRepositoryInterface $stages,
        protected UserRepositoryInterface $users
    ) {}

    public function index(Request $request)
    {
        $utilisateur = auth()->user();

        $filtres = $request->only(['statut', 'stagiaire_id', 'date_debut', 'date_fin']);

        $stages = match (true) {
            $utilisateur->isAdmin() => $this->stages->paginateAll(),
            $utilisateur->isMentor() => $this->stages->paginateForMentor($utilisateur->id, $filtres),
            default => $this->stages->paginateForStagiaire($utilisateur->id),
        };

        $transitionsParStage = collect($stages->items())->mapWithKeys(
            fn (Stage $stage) => [$stage->id => $this->stageService->transitionsAutorisees($stage)]
        );

        $stagiairesActifs = match (true) {
            $utilisateur->isAdmin() => $this->stages->findActifs(),
            $utilisateur->isMentor() => $this->stages->findByMentor($utilisateur->id)->where('statut', 'en_cours')->values(),
            default => collect(),
        };

        return view('stages.index', compact('stages', 'transitionsParStage', 'stagiairesActifs'));
    }

    public function create()
    {
        $this->authorize('create', Stage::class);

        $mentors = $this->users->all()->where('role', 'mentor');
        $stagiaires = $this->users->all()->where('role', 'stagiaire');
        return view('stages.create', compact('mentors', 'stagiaires'));
    }

    public function store(StoreStageRequest $request)
    {
        $stage = $this->stageService->creer($request->validated());

        return redirect()
            ->route('stages.index')
            ->with('success', 'Stage créé avec succès.');
    }

    public function updateStatus(UpdateStageStatusRequest $request, Stage $stage)
    {
        $this->stageService->changerStatut($stage, $request->validated('statut'));

        return back()->with('success', 'Statut du stage mis à jour.');
    }

    public function destroy(Stage $stage)
    {
        $this->authorize('delete', $stage);

        $this->stageService->supprimer($stage);

        return redirect()
            ->route('stages.index')
            ->with('success', 'Stage supprimé.');
    }
}
