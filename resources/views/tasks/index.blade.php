@extends('layouts.app')

@section('title', 'Tâches')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title h3 mb-0"><i class="bi bi-list-check me-2"></i>Tâches</h1>
        @if (auth()->user()->isAdmin() || auth()->user()->isMentor())
            <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Créer une tâche
            </a>
        @endif
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Projet</th>
                        <th>Priorité</th>
                        <th>Statut</th>
                        <th style="min-width:180px">Changer le statut</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tasks as $task)
                        @php
                            $couleursPriorite = ['basse' => 'bg-secondary', 'moyenne' => 'bg-warning text-dark', 'haute' => 'bg-danger'];
                            $couleursStatutTache = ['a_faire' => 'bg-secondary', 'en_cours' => 'bg-warning text-dark', 'en_revue' => 'bg-info text-dark', 'termine' => 'bg-success'];
                            $libellesStatut = ['a_faire' => 'À faire', 'en_cours' => 'En cours', 'en_revue' => 'En revue', 'termine' => 'Terminé'];
                            $transitions = $transitionsParTache[$task->id] ?? [];
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('tasks.show', $task) }}" class="text-decoration-none fw-semibold">
                                    {{ $task->titre }}
                                </a>
                            </td>
                            <td>{{ $task->project->nom }}</td>
                            <td><span class="badge {{ $couleursPriorite[$task->priorite] ?? 'bg-secondary' }}">{{ $task->priorite }}</span></td>
                            <td><span class="badge {{ $couleursStatutTache[$task->statut] ?? 'bg-secondary' }}">{{ $task->statut }}</span></td>
                            <td>
                                @if (count($transitions) > 0)
                                    <form method="POST" action="{{ route('tasks.updateStatus', $task) }}">
                                        @csrf
                                        @method('PATCH')
                                        <select name="statut" onchange="this.form.submit()" class="form-select form-select-sm">
                                            <option value="{{ $task->statut }}" selected>{{ $libellesStatut[$task->statut] ?? $task->statut }}</option>
                                            @foreach ($transitions as $transition)
                                                <option value="{{ $transition }}">{{ $libellesStatut[$transition] ?? $transition }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @else
                                    <span class="text-muted small">Aucune action possible</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Aucune tâche pour le moment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($tasks->hasPages())
            <div class="card-footer bg-white">
                {{ $tasks->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
