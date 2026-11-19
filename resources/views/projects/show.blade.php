@extends('layouts.app')

@section('title', $project->nom)

@section('content')
    @php
        $groupe = $project->groupe;
        $peutDeposer = auth()->user()->can('deposerDocument', $project);
    @endphp

    <x-fil-ariane :items="[['Projets', route('projects.index')], [$project->nom]]" />

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h1 class="page-title h3 mb-0"><i class="bi bi-folder2-open me-2"></i>{{ $project->nom }}</h1>
        <div class="d-flex gap-2 flex-wrap">
            @can('gererTaches', $project)
                <a href="{{ route('tasks.create', ['project_id' => $project->id]) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>Créer une tâche
                </a>
            @endcan
            <a href="{{ route('kanban.show', $project->id) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-kanban me-1"></i>Kanban
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-5">
            {{-- Équipe et avancement --}}
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-graph-up-arrow me-1"></i>Équipe et avancement</div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <x-anneau :pourcentage="$progression['pourcentage']" taille="5.5rem" libelle="Avancement" />
                        <div class="small">
                            <div class="fw-semibold mb-1">{{ $voitToutLeProjet ? 'Avancement du projet' : 'Ma progression' }}</div>
                            <div class="text-muted">{{ $progression['terminees'] }} tâche{{ $progression['terminees'] > 1 ? 's' : '' }} terminée{{ $progression['terminees'] > 1 ? 's' : '' }} sur {{ $progression['total'] }}</div>
                            @if ($progression['en_retard'] > 0)
                                <span class="pastille pastille-alerte mt-1"><i class="bi bi-exclamation-triangle-fill"></i>{{ $progression['en_retard'] }} en retard</span>
                            @endif
                        </div>
                    </div>

                    @if ($groupe)
                        <div class="small text-muted mb-2">Équipe</div>
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                            @foreach ($groupe->membres as $membre)
                                <li class="d-flex align-items-center gap-2">
                                    <x-avatar :user="$membre" />
                                    <span class="flex-grow-1">{{ $membre->name }}</span>
                                    @if ($membre->id === $groupe->chef_id)
                                        <span class="pastille pastille-attention"><i class="bi bi-star-fill"></i>Chef</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <x-etat-vide icone="bi-people" titre="Pas encore d'équipe" texte="Un groupe sera associé à ce projet par le mentor." class="py-3" />
                    @endif
                </div>
            </div>

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
