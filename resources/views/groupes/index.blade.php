@extends('layouts.app')

@section('title', 'Groupes')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title h3 mb-0"><i class="bi bi-people me-2"></i>Groupes de stagiaires</h1>
        <a href="{{ route('groupes.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Créer un groupe
        </a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Groupe</th>
                        <th>Membres</th>
                        <th>Chef de groupe</th>
                        <th>Projet</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($groupes as $groupe)
                        <tr>
                            <td>{{ $groupe->nom }}</td>
                            <td>{{ $groupe->membres->pluck('name')->join(', ') }}</td>
                            <td>{{ $groupe->chef?->name ?? "Compte supprimé" }}</td>
                            <td>
                                <a href="{{ route('projects.show', $groupe->project_id) }}">{{ $groupe->project->nom }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Aucun groupe pour le moment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($groupes->hasPages())
            <div class="card-footer bg-white">
                {{ $groupes->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
