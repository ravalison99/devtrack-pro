<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGroupeRequest;
use App\Models\Groupe;
use App\Repositories\Contracts\GroupeRepositoryInterface;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\GroupeService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class GroupeController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected GroupeService $groupeService,
        protected GroupeRepositoryInterface $groupes,
        protected ProjectRepositoryInterface $projects,
        protected UserRepositoryInterface $users
    ) {}

    public function index()
    {
        $this->authorize('viewAny', Groupe::class);

        $groupes = $this->groupes->paginateForMentor(auth()->id());

        return view('groupes.index', compact('groupes'));
    }

    public function create()
    {
        $this->authorize('create', Groupe::class);

        return view('groupes.create', [
            'stagiaires' => $this->users->findStagiairesSansGroupe(),
            'projects' => $this->projects->findNonAffectesForMentor(auth()->id()),
            'maxMembres' => Groupe::MAX_MEMBRES,
        ]);
    }

    public function store(StoreGroupeRequest $request)
    {
        $this->groupeService->creer($request->user(), $request->validated());

        return redirect()
            ->route('groupes.index')
            ->with('success', 'Groupe créé avec succès.');
    }
}
