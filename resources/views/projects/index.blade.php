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
                                    <span class="badge bg-success">Affecté</span>
                                @else
                                    <span class="badge bg-warning text-dark">Non affecté</span>
                                @endif
                            </td>
                            <td>
                                @if ($project->archive)
                                    <span class="badge bg-secondary">Oui</span>
                                @else
                                    <span class="badge bg-success">Non</span>
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
                            <td colspan="4" class="text-center text-muted py-4">Aucun projet pour le moment.</td>
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
