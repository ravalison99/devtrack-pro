@extends('layouts.app')

@section('title', 'Créer une tâche')

@section('content')
    <h1 class="page-title h3 mb-4"><i class="bi bi-list-check me-2"></i>Créer une tâche</h1>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-body p-4">
                    @if ($projects->isEmpty())
                        <p class="text-muted mb-0">Aucun projet disponible pour le moment. Créez d'abord un projet.</p>
                    @else
                        <form method="POST" action="{{ route('tasks.store') }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Projet</label>
                                <select name="project_id" class="form-select">
                                    @foreach ($projects as $project)
                                        <option value="{{ $project->id }}" @selected(($projetSelectionne?->id ?? old('project_id')) == $project->id)>
                                            {{ $project->nom }} — {{ $project->stage->stagiaire->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Titre</label>
                                <input type="text" name="titre" value="{{ old('titre') }}" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" rows="4" class="form-control">{{ old('description') }}</textarea>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Priorité</label>
                                    <select name="priorite" class="form-select">
                                        <option value="basse">Basse</option>
                                        <option value="moyenne" selected>Moyenne</option>
                                        <option value="haute">Haute</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Date d'échéance</label>
                                    <input type="date" name="date_echeance" value="{{ old('date_echeance') }}" class="form-control">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary mt-4">
                                <i class="bi bi-check-lg me-1"></i>Créer
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
