<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStageRequest;
use App\Services\StageService;

class StageController extends Controller
{
    public function __construct(protected StageService $stageService) {}

    public function store(StoreStageRequest $request)
    {
        $stage = $this->stageService->creer($request->validated());

        return redirect()
            ->route('stages.index')
            ->with('success', 'Stage créé avec succès.');
    }
}
