@extends('layouts.app')

@section('title', 'Modifier une tâche')

@section('content')
    <a href="{{ route('tasks.index') }}" class="text-decoration-none small text-muted d-inline-block mb-2">
        <i class="bi bi-arrow-left me-1"></i>Retour aux tâches
    </a>

    <h1 class="page-title h3 mb-1"><i class="bi bi-list-check me-2"></i>Modifier la tâche</h1>
    <p class="text-muted mb-4">Projet : {{ $task->project->nom }}</p>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('tasks.update', $task) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Titre</label>
                            <input type="text" name="titre" value="{{ old('titre', $task->titre) }}" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="4" class="form-control">{{ old('description', $task->description) }}</textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Priorité</label>
                                <select name="priorite" class="form-select">
                                    <option value="basse" @selected(old('priorite', $task->priorite) === 'basse')>Basse</option>
                                    <option value="moyenne" @selected(old('priorite', $task->priorite) === 'moyenne')>Moyenne</option>
                                    <option value="haute" @selected(old('priorite', $task->priorite) === 'haute')>Haute</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Date d'échéance</label>
                                <input type="date" name="date_echeance" value="{{ old('date_echeance', $task->date_echeance?->format('Y-m-d')) }}" class="form-control">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary mt-4">
                            <i class="bi bi-check-lg me-1"></i>Enregistrer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
