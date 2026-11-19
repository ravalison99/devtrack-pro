@extends('layouts.app')

@section('title', 'Documents')

@section('content')
    @php
        $utilisateur = auth()->user();
        $voitTout = $utilisateur->isAdmin() || $utilisateur->isMentor();
        $nbFiltres = collect($filtres)->filter(fn ($valeur) => $valeur !== null && $valeur !== '')->count();
        $couleursOrigine = ['projet' => 'bg-primary', 'cahier' => 'bg-dark', 'tache' => 'bg-info text-dark', 'personnel' => 'bg-secondary'];
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-1">
        <h1 class="page-title h3 mb-0"><i class="bi bi-file-earmark-text me-2"></i>Documents</h1>
        <span class="text-muted small">{{ $lignes->total() }} document{{ $lignes->total() > 1 ? 's' : '' }}</span>
    </div>
    <p class="text-muted mb-4">
        @if ($voitTout)
            Tous les documents de l'application, avec ce à quoi ils sont reliés et la personne qui les a déposés.
        @else
            Les documents des projets qui vous sont attribués.
        @endif
    </p>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('documents.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-lg-6">
                    <label class="form-label small text-muted mb-1">Recherche</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="search" name="q" value="{{ $filtres['q'] ?? '' }}" maxlength="100" class="form-control"
                               placeholder="Nom du document ou du fichier, projet, groupe, tâche, personne…">
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <label class="form-label small text-muted mb-1">Origine</label>
                    <select name="origine" class="form-select">
                        <option value="">Toutes</option>
                        @foreach ($origines as $cle => $libelle)
                            <option value="{{ $cle }}" @selected(($filtres['origine'] ?? null) === $cle)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-lg-3">
                    <label class="form-label small text-muted mb-1">Projet</label>
                    <select name="project_id" class="form-select">
                        <option value="">Tous</option>
                        @foreach ($projets as $projet)
                            <option value="{{ $projet->id }}" @selected((string) ($filtres['project_id'] ?? '') === (string) $projet->id)>{{ $projet->nom }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-lg-3">
                    <label class="form-label small text-muted mb-1">Catégorie</label>
                    <select name="categorie" class="form-select">
                        <option value="">Toutes</option>
                        @foreach ($categories as $categorie)
                            <option value="{{ $categorie }}" @selected(($filtres['categorie'] ?? null) === $categorie)>{{ $categorie }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-lg-3">
                    <label class="form-label small text-muted mb-1">Type de rapport</label>
                    <select name="type_rapport" class="form-select">
                        <option value="">Tous</option>
                        @foreach ($typesRapport as $cle => $libelle)
                            <option value="{{ $cle }}" @selected(($filtres['type_rapport'] ?? null) === $cle)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-lg-2">
                    <label class="form-label small text-muted mb-1">Déposé à partir du</label>
                    <input type="date" name="date_debut" value="{{ $filtres['date_debut'] ?? '' }}" class="form-control">
                </div>

                <div class="col-6 col-lg-2">
                    <label class="form-label small text-muted mb-1">Jusqu'au</label>
                    <input type="date" name="date_fin" value="{{ $filtres['date_fin'] ?? '' }}" class="form-control">
                </div>

                <div class="col-12 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-funnel me-1"></i>Filtrer</button>
                    @if ($nbFiltres > 0)
                        <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary" title="Réinitialiser les filtres">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </form>
            <div class="form-text mt-2">La catégorie et le type de rapport ne concernent que les documents déposés par les chefs de groupe.</div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Document</th>
                        <th>Origine</th>
                        <th>Relié à</th>
                        <th>Déposé par</th>
                        <th>Date</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lignes as $ligne)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $ligne->titre }}</div>
                                @if ($ligne->nomFichier && $ligne->nomFichier !== $ligne->titre)
                                    <div class="text-muted small"><i class="bi bi-file-earmark me-1"></i>{{ $ligne->nomFichier }}</div>
                                @endif
                                <div class="mt-1">
                                    @if ($ligne->categorie)
                                        <span class="badge bg-light text-dark border">{{ $ligne->categorie }}</span>
                                    @endif
                                    @if ($ligne->typeRapport)
                                        <span class="badge bg-info text-dark">{{ $ligne->typeRapport }}</span>
                                    @endif
                                    @if ($ligne->versionActuelle)
                                        <span class="badge bg-success">Version {{ $ligne->versionActuelle }}</span>
                                        @if ($ligne->nombreVersions > 1)
                                            <span class="text-muted small">({{ $ligne->nombreVersions }} versions)</span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                            <td><span class="badge {{ $couleursOrigine[$ligne->origine] ?? 'bg-secondary' }}">{{ $ligne->libelleOrigine() }}</span></td>
                            <td class="small">
                                @if ($ligne->projet)
                                    <div>
                                        <i class="bi bi-folder2-open me-1 text-muted"></i>Projet :
                                        @can('view', $ligne->projet)
                                            <a href="{{ route('projects.show', $ligne->projet->id) }}">{{ $ligne->projet->nom }}</a>
                                        @else
                                            {{ $ligne->projet->nom }}
                                        @endcan
                                    </div>
                                    <div>
                                        <i class="bi bi-people me-1 text-muted"></i>Groupe :
                                        @if ($ligne->groupe)
                                            {{ $ligne->groupe->nom }}
                                            <span class="text-muted">(chef : {{ $ligne->groupe->chef?->name ?? 'Compte supprimé' }})</span>
                                        @else
                                            <span class="text-muted">aucun groupe</span>
                                        @endif
                                    </div>
                                @elseif ($ligne->origine === 'personnel')
                                    <span class="text-muted">Aucun projet (ancien document personnel)</span>
                                @else
                                    <span class="text-muted">Projet supprimé</span>
                                @endif
                                @if ($ligne->tache)
                                    <div>
                                        <i class="bi bi-list-check me-1 text-muted"></i>Tâche :
                                        @can('view', $ligne->tache)
                                            <a href="{{ route('tasks.show', $ligne->tache) }}">{{ $ligne->tache->titre }}</a>
                                        @else
                                            {{ $ligne->tache->titre }}
                                        @endcan
                                    </div>
                                @endif
                            </td>
                            <td class="small">
                                @if ($ligne->deposant)
                                    {{ $ligne->deposant->name }}
                                    <div><span class="badge rounded-pill bg-light text-dark border text-uppercase">{{ $ligne->deposant->role }}</span></div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small text-nowrap">{{ $ligne->date?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="text-end">
                                @if ($ligne->urlTelechargement)
                                    <a href="{{ $ligne->urlTelechargement }}" class="btn btn-sm btn-outline-primary text-nowrap">
                                        <i class="bi bi-download me-1"></i>Télécharger
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                @if ($nbFiltres > 0)
                                    Aucun document ne correspond à votre recherche.
                                @else
                                    Aucun document pour le moment.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($lignes->hasPages())
            <div class="card-footer bg-white">
                {{ $lignes->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
