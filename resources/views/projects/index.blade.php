@extends('layouts.app')

@section('title', 'Projets')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title h3 mb-0"><i class="bi bi-folder2-open me-2"></i>Projets</h1>

        @can('create', \App\Models\Project::class)
            <a href="{{ route('projects.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-folder-plus me-1"></i>Créer un projet
            </a>
        @endcan
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nom du projet</th>
                        <th>État d'affectation</th>
                        <th>Archivé</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($projects as $project)
                        <tr>
                            <td>{{ $project->nom }}</td>
                            <td>
                                {{-- Valeur réelle : un groupe est-il associé à ce projet en base ? --}}
                                @if ($project->estAffecte())
                                    <span class="pastille pastille-succes"><i class="bi bi-people-fill"></i>Affecté</span>
                                @else
                                    <span class="pastille pastille-attention"><i class="bi bi-hourglass-split"></i>Non affecté</span>
                                @endif
                            </td>
                            <td>
                                @if ($project->archive)
                                    <span class="pastille pastille-neutre"><i class="bi bi-archive"></i>Oui</span>
                                @else
                                    <span class="pastille pastille-succes">Non</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('projects.show', $project->id) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye me-1"></i>Détails
                                </a>
                                <a href="{{ route('kanban.show', $project->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-kanban me-1"></i>Kanban
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4"><x-etat-vide icone="bi-folder2-open" titre="Aucun projet pour le moment" texte="Les projets qui vous sont attribués apparaîtront ici." /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($projects->hasPages())
            <div class="card-footer bg-white">
                {{ $projects->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
