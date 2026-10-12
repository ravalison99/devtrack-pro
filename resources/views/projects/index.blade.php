@extends('layouts.app')

@section('title', 'Projets')

@section('content')
    <h1 class="page-title h3 mb-4"><i class="bi bi-folder2-open me-2"></i>Projets</h1>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Description</th>
                        <th>Stagiaires</th>
                        <th>Archivé</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($projects as $project)
                        @php
                            $estMentorDuProjet = auth()->user()->isMentor() && auth()->id() === $project->stage->mentor_id;
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('projects.show', $project) }}" class="text-decoration-none fw-semibold">
                                    {{ $project->nom }}
                                </a>
                            </td>
                            <td>
                                @if ($project->description)
                                    <span class="d-inline-block text-truncate align-middle" style="max-width: 280px;" title="{{ $project->description }}">
                                        {{ $project->description }}
                                    </span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>{{ $project->stage->stagiaire->name }}</td>
                            <td>
                                @if ($project->archive)
                                    <span class="badge bg-secondary">Oui</span>
                                @else
                                    <span class="badge bg-success">Non</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('kanban.show', $project->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-kanban me-1"></i>Kanban
                                    </a>
                                    @if ($estMentorDuProjet)
                                        <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Supprimer ce projet ? Les tâches associées seront également supprimées.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Aucun projet pour le moment.</td>
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
