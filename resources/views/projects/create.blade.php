@extends('layouts.app')

@section('title', 'Créer un projet')

@section('content')
    <h1 class="page-title h3 mb-1"><i class="bi bi-folder2-open me-2"></i>Créer un projet</h1>
    <p class="text-muted mb-4">Le projet sera affecté à un groupe lors de la création du groupe.</p>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('projects.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Nom du projet</label>
                            <input type="text" name="nom" value="{{ old('nom') }}" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="4" class="form-control">{{ old('description') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cahier des charges</label>
                            <input type="file" name="cahier_charges" class="form-control" accept=".pdf,.doc,.docx" required>
                            <div class="form-text">PDF, DOC ou DOCX — 5 Mo maximum.</div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Créer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
