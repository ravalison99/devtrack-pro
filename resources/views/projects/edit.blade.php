@extends('layouts.app')

@section('title', 'Modifier un projet')

@section('content')
    <a href="{{ route('projects.index') }}" class="text-decoration-none small text-muted d-inline-block mb-2">
        <i class="bi bi-arrow-left me-1"></i>Retour aux projets
    </a>

    <h1 class="page-title h3 mb-1"><i class="bi bi-folder2-open me-2"></i>Modifier le projet</h1>
    <p class="text-muted mb-4">
        Stage #{{ $project->stage->id }} — Stagiaire : {{ $project->stage->stagiaire->name }}
    </p>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('projects.update', $project) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Nom du projet</label>
                            <input type="text" name="nom" value="{{ old('nom', $project->nom) }}" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="4" class="form-control">{{ old('description', $project->description) }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Enregistrer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
