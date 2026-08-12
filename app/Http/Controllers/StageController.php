<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStageRequest;
use App\Repositories\Contracts\StageRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\StageService;

class StageController extends Controller
{
    public function __construct(
        protected StageService $stageService,
        protected StageRepositoryInterface $stages,
        protected UserRepositoryInterface $users
    ) {}

    public function index()
    {
        $stages = $this->stages->all();
        return view('stages.index', compact('stages'));
    }

    public function create()
    {
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
}
