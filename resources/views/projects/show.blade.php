@extends('layouts.app')

@section('title', $project->nom)

@section('content')
    @php
        $groupe = $project->groupe;
        $peutDeposer = auth()->user()->can('deposerDocument', $project);
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title h3 mb-0"><i class="bi bi-folder2-open me-2"></i>{{ $project->nom }}</h1>
        <div class="d-flex gap-2">
            @can('gererTaches', $project)
                <a href="{{ route('tasks.create', ['project_id' => $project->id]) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>Créer une tâche
                </a>
            @endcan
            <a href="{{ route('kanban.show', $project->id) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-kanban me-1"></i>Kanban
            </a>
            <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Retour aux projets
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-info-circle me-1"></i>Informations</div>
                <div class="card-body">
                    <dl class="mb-0">
                        <dt>Nom du projet</dt>
                        <dd>{{ $project->nom }}</dd>

                        <dt>Groupe de stagiaires</dt>
                        <dd>
                            @if ($groupe)
                                {{ $groupe->nom }}
                            @else
                                <span class="text-muted">Aucun groupe n'est associé à ce projet.</span>
                            @endif
                        </dd>

                        @if ($groupe)
                            <dt>Chef de groupe</dt>
                            <dd>{{ $groupe->chef?->name ?? "Compte supprimé" }}</dd>

                            <dt>Membres</dt>
                            <dd>{{ $groupe->membres->pluck('name')->join(', ') }}</dd>
                        @endif

                        <dt>Cahier des charges</dt>
                        <dd class="mb-0">
                            @if ($project->aUnCahierDesCharges())
                                <a href="{{ route('projects.cahier.download', $project->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-download me-1"></i>{{ $project->cahier_charges_nom }}
                                </a>
                            @else
                                <span class="text-muted">Aucun cahier des charges téléversé.</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            @if ($peutDeposer)
                <div class="card">
                    <div class="card-header"><i class="bi bi-cloud-upload me-1"></i>Déposer un document</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('projects.documents.store', $project->id) }}" enctype="multipart/form-data">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Nom du document</label>
                                <input type="text" name="titre" value="{{ old('titre') }}" class="form-control" required>
                                <div class="form-text">Un nouveau dépôt avec le même nom et le même type de rapport crée une nouvelle version.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Catégorie</label>
                                <select name="categorie" class="form-select" required>
                                    <option value="">— Sélectionner —</option>
                                    @foreach (\App\Models\Document::CATEGORIES as $categorie)
                                        <option value="{{ $categorie }}" @selected(old('categorie') === $categorie)>{{ $categorie }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Type de rapport</label>
                                <select name="type_rapport" class="form-select" required>
                                    <option value="">— Sélectionner —</option>
                                    @foreach (\App\Models\Document::TYPES_RAPPORT as $valeur => $libelle)
                                        <option value="{{ $valeur }}" @selected(old('type_rapport') === $valeur)>{{ $libelle }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Fichier</label>
                                <input type="file" name="fichier" class="form-control" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" required>
                                <div class="form-text">PDF, DOC, DOCX, PNG ou JPG — 5 Mo maximum.</div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>Déposer
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-header"><i class="bi bi-file-earmark-bar-graph me-1"></i>Rapports et documents soumis</div>
                <div class="list-group list-group-flush">
                    @forelse ($project->documents as $document)
                        @include('documents._document-projet', ['document' => $document])
                    @empty
                        <div class="list-group-item text-center text-muted py-4">Aucun rapport soumis pour ce projet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
