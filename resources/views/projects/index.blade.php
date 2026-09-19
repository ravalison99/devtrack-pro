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
                        <th>Stage</th>
                        <th>Archivé</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($projects as $project)
                        <tr>
                            <td>{{ $project->nom }}</td>
                            <td>{{ $project->stage->stagiaire->name }}</td>
                            <td>
                                @if ($project->archive)
                                    <span class="badge bg-secondary">Oui</span>
                                @else
                                    <span class="badge bg-success">Non</span>
                                @endif
                            </td>
                            <td class="text-end">
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
