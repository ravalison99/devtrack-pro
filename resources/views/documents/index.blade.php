@extends('layouts.app')

@section('title', 'Documents')

@section('content')
    <h1 class="page-title h3 mb-4"><i class="bi bi-file-earmark-text me-2"></i>Mes documents</h1>

    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-header"><i class="bi bi-cloud-upload me-1"></i>Déposer un document</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Titre</label>
                            <input type="text" name="titre" value="{{ old('titre') }}" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catégorie</label>
                            <select name="categorie" class="form-select">
                                <option value="">— Aucune —</option>
                                @foreach (\App\Models\Document::CATEGORIES as $categorie)
                                    <option value="{{ $categorie }}" @selected(old('categorie') === $categorie)>{{ $categorie }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fichier</label>
                            <input type="file" name="fichier" class="form-control">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Déposer
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-header"><i class="bi bi-folder2 me-1"></i>Mes documents</div>
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
                        <div class="list-group-item text-center text-muted py-4">Aucun document déposé pour le moment.</div>
                    @endforelse
                </div>
                @if ($documents->hasPages())
                    <div class="card-footer bg-white">
                        {{ $documents->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
