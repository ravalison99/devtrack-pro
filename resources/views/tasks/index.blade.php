@extends('layouts.app')

@section('title', auth()->user()->isStagiaire() ? 'Mes tâches' : 'Tâches')

@section('content')
    @php
        $statutActif = $filtres['statut'] ?? null;
        $retardActif = ! empty($filtres['retard']);
        $recherche = $filtres['q'] ?? '';
        $filtreActif = $statutActif || $retardActif || $recherche !== '';
        $pills = [
            [null, 'Toutes', []],
            ['a_faire', 'À faire', ['statut' => 'a_faire']],
            ['en_cours', 'En cours', ['statut' => 'en_cours']],
            ['en_revue', 'En revue', ['statut' => 'en_revue']],
            ['termine', 'Terminées', ['statut' => 'termine']],
        ];
        $peutCreer = auth()->user()->isAdmin() || auth()->user()->isMentor() || auth()->user()->estChefDeGroupe();
    @endphp

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h1 class="page-title h3 mb-0"><i class="bi bi-list-check me-2"></i>{{ auth()->user()->isStagiaire() ? 'Mes tâches' : 'Tâches' }}</h1>
        @if ($peutCreer)
            <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Créer une tâche
            </a>
        @endif
    </div>

    {{-- Filtres --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div class="filtres-pills">
            @foreach ($pills as [$cle, $libelle, $params])
                <a href="{{ route('tasks.index', $params) }}" class="{{ ! $retardActif && $statutActif === $cle ? 'actif' : '' }}">{{ $libelle }}</a>
            @endforeach
            <a href="{{ route('tasks.index', ['retard' => 1]) }}" class="{{ $retardActif ? 'actif' : '' }}"><i class="bi bi-exclamation-triangle me-1"></i>En retard</a>
        </div>

        <form method="GET" action="{{ route('tasks.index') }}" class="d-flex gap-2" role="search">
            @if ($statutActif)<input type="hidden" name="statut" value="{{ $statutActif }}">@endif
            @if ($retardActif)<input type="hidden" name="retard" value="1">@endif
            <div class="input-group" style="min-width:15rem">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" name="q" value="{{ $recherche }}" maxlength="100" class="form-control" placeholder="Rechercher une tâche…" aria-label="Rechercher une tâche">
            </div>
            @if ($filtreActif)
                <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary" title="Réinitialiser les filtres"><i class="bi bi-x-lg"></i></a>
            @endif
        </form>
    </div>

    <div class="card">
        @forelse ($tasks as $task)
            @php $transitions = $transitionsParTache[$task->id] ?? []; @endphp

            {{-- Téléphone : une carte par tâche --}}
            <div class="d-md-none border-bottom">
                <div class="tache-carte prio-{{ $task->priorite }}">
                    <a href="{{ route('tasks.show', $task) }}" class="lien-tache d-block mb-1">{{ $task->titre }}</a>
                    <div class="small text-muted mb-2 tronque"><i class="bi bi-folder2-open me-1"></i>{{ $task->project->nom }}</div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <x-badge-statut :statut="$task->statut" />
                        <x-badge-priorite :priorite="$task->priorite" />
                        <x-echeance :date="$task->date_echeance" :statut="$task->statut" />
                    </div>
                    <div class="d-flex justify-content-between align-items-center gap-2">
                        <span class="small d-flex align-items-center gap-2">
                            @if ($task->assigne)<x-avatar :user="$task->assigne" taille="sm" />{{ $task->assigne->name }}@else<span class="text-muted">Non assignée</span>@endif
                        </span>
                        @include('tasks._changer-statut', ['task' => $task, 'transitions' => $transitions])
                    </div>
                </div>
            </div>
        @empty
            <div class="d-md-none">
                @include('tasks._vide', ['filtreActif' => $filtreActif, 'peutCreer' => $peutCreer])
            </div>
        @endforelse

        {{-- Ordinateur : tableau --}}
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tâche</th>
                        <th>Assignée à</th>
                        <th>Priorité</th>
                        <th>Échéance</th>
                        <th>Statut</th>
                        <th style="min-width:170px">Changer le statut</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tasks as $task)
                        @php $transitions = $transitionsParTache[$task->id] ?? []; @endphp
                        <tr>
                            <td>
                                <a href="{{ route('tasks.show', $task) }}" class="lien-tache">{{ $task->titre }}</a>
                                <div class="small text-muted"><i class="bi bi-folder2-open me-1"></i>{{ $task->project->nom }}</div>
                            </td>
                            <td>
                                @if ($task->assigne)
                                    <span class="d-flex align-items-center gap-2"><x-avatar :user="$task->assigne" taille="sm" /><span class="small">{{ $task->assigne->name }}</span></span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td><x-badge-priorite :priorite="$task->priorite" /></td>
                            <td><x-echeance :date="$task->date_echeance" :statut="$task->statut" /></td>
                            <td><x-badge-statut :statut="$task->statut" /></td>
                            <td>@include('tasks._changer-statut', ['task' => $task, 'transitions' => $transitions])</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">@include('tasks._vide', ['filtreActif' => $filtreActif, 'peutCreer' => $peutCreer])</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tasks->hasPages())
            <div class="card-footer">
                {{ $tasks->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
