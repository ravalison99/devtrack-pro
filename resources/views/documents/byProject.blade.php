@extends('layouts.app')

@section('title', 'Documents — ' . $project->nom)

@section('content')
    <a href="{{ route('kanban.show', $project->id) }}" class="text-decoration-none small text-muted d-inline-block mb-2">
        <i class="bi bi-arrow-left me-1"></i>Retour au Kanban
    </a>

    <h1 class="page-title h3 mb-4"><i class="bi bi-file-earmark-text me-2"></i>Documents — {{ $project->nom }}</h1>

    <div class="card">
        <div class="card-header"><i class="bi bi-folder2 me-1"></i>Documents du projet</div>
        <div class="list-group list-group-flush">
            @forelse ($documents as $document)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong>{{ $document->titre }}</strong>
                        <span class="badge bg-light text-dark border">{{ $document->categorie ?? 'Sans catégorie' }}</span>
                    </div>
                    <ul class="list-unstyled mb-0 small">
                        @foreach ($document->versions as $version)
                            <li class="d-flex justify-content-between align-items-center py-1">
                                <span>Version {{ $version->numero_version }}</span>
                                <a href="{{ route('documents.download', [$document->id, $version->id]) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-download me-1"></i>Télécharger
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <div class="list-group-item text-center text-muted py-4">Aucun document déposé pour ce projet.</div>
            @endforelse
        </div>
    </div>
@endsection
