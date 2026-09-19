@extends('layouts.app')

@section('title', 'Tableau de bord')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title h3 mb-0"><i class="bi bi-speedometer2 me-2"></i>Tableau de bord</h1>

        @if (auth()->user()->isMentor())
            <div class="d-flex gap-2">
                <a href="{{ route('projects.create.mentor') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-folder-plus me-1"></i>Créer un projet
                </a>
                <a href="{{ route('tasks.create') }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>Créer une tâche
                </a>
            </div>
        @endif
    </div>

    @if (auth()->user()->isStagiaire() && isset($indicateurs['stage']) && $indicateurs['stage'])
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-mortarboard me-1"></i>Mon stage</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small">Mon mentor</div>
                        @if ($indicateurs['mentor'])
                            <div class="fw-semibold">{{ $indicateurs['mentor']->name }}</div>
                            <div class="text-muted small">{{ $indicateurs['mentor']->email }}</div>
                        @else
                            <div class="text-muted">Aucun mentor associé.</div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Période du stage</div>
                        <div class="fw-semibold">
                            {{ $indicateurs['stage']->date_debut->format('d/m/Y') }} — {{ $indicateurs['stage']->date_fin->format('d/m/Y') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @elseif (auth()->user()->isStagiaire())
        <div class="alert alert-warning">Aucun stage actif pour le moment.</div>
    @endif

    <div class="row g-3">
        {{-- Admin --}}
        @if (isset($indicateurs['total_utilisateurs']))
            @php
                $tuilesAdmin = [
                    ['label' => 'Utilisateurs', 'valeur' => $indicateurs['total_utilisateurs'], 'couleur' => '#4f46e5', 'icone' => 'bi-people'],
                    ['label' => 'Stagiaires', 'valeur' => $indicateurs['total_stagiaires'], 'couleur' => '#0284c7', 'icone' => 'bi-person'],
                    ['label' => 'Mentors', 'valeur' => $indicateurs['total_mentors'], 'couleur' => '#0d9488', 'icone' => 'bi-person-badge'],
                    ['label' => 'Stages au total', 'valeur' => $indicateurs['total_stages'], 'couleur' => '#6b7280', 'icone' => 'bi-mortarboard'],
                    ['label' => 'Stages en cours', 'valeur' => $indicateurs['stages_en_cours'], 'couleur' => '#4338ca', 'icone' => 'bi-mortarboard-fill'],
                    ['label' => 'Stages terminés', 'valeur' => $indicateurs['stages_termines'], 'couleur' => '#16a34a', 'icone' => 'bi-check-circle'],
                    ['label' => 'Stages annulés', 'valeur' => $indicateurs['stages_annules'], 'couleur' => '#dc2626', 'icone' => 'bi-x-circle'],
                    ['label' => 'Projets', 'valeur' => $indicateurs['total_projets'], 'couleur' => '#d97706', 'icone' => 'bi-folder2-open'],
                    ['label' => 'Tâches', 'valeur' => $indicateurs['total_taches'], 'couleur' => '#0284c7', 'icone' => 'bi-list-check'],
                ];
            @endphp

            @foreach ($tuilesAdmin as $tuile)
                <div class="col-6 col-lg-3">
                    <div class="stat-card" style="background:{{ $tuile['couleur'] }};">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-label">{{ $tuile['label'] }}</div>
                                <div class="stat-value">{{ $tuile['valeur'] }}</div>
                            </div>
                            <i class="bi {{ $tuile['icone'] }} fs-4 opacity-75"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif

        {{-- Mentor --}}
        @if (isset($indicateurs['mes_stagiaires']))
            @php
                $tuilesMentor = [
                    ['label' => 'Stagiaires encadrés', 'valeur' => $indicateurs['mes_stagiaires'], 'couleur' => '#4f46e5', 'icone' => 'bi-people'],
                    ['label' => 'Mes stages', 'valeur' => $indicateurs['mes_stages'], 'couleur' => '#6b7280', 'icone' => 'bi-mortarboard'],
                    ['label' => 'Stages en cours', 'valeur' => $indicateurs['stages_en_cours'], 'couleur' => '#4338ca', 'icone' => 'bi-mortarboard-fill'],
                    ['label' => 'Mes projets', 'valeur' => $indicateurs['mes_projets'], 'couleur' => '#d97706', 'icone' => 'bi-folder2-open'],
                    ['label' => 'Mes tâches', 'valeur' => $indicateurs['mes_taches'], 'couleur' => '#0284c7', 'icone' => 'bi-list-check'],
                    ['label' => 'Tâches en cours', 'valeur' => $indicateurs['taches_en_cours'], 'couleur' => '#d97706', 'icone' => 'bi-arrow-repeat'],
                    ['label' => 'Tâches terminées', 'valeur' => $indicateurs['taches_terminees'], 'couleur' => '#16a34a', 'icone' => 'bi-check-circle'],
                    ['label' => 'Tâches en retard', 'valeur' => $indicateurs['taches_en_retard'], 'couleur' => '#dc2626', 'icone' => 'bi-exclamation-triangle'],
                    ['label' => 'rapports reçus', 'valeur' => $indicateurs['rapports_recus'], 'couleur' => '#0d9488', 'icone' => 'bi-file-earmark-bar-graph'],
                ];
            @endphp

            @foreach ($tuilesMentor as $tuile)
                <div class="col-6 col-lg-3">
                    <div class="stat-card" style="background:{{ $tuile['couleur'] }};">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-label">{{ $tuile['label'] }}</div>
                                <div class="stat-value">{{ $tuile['valeur'] }}</div>
                            </div>
                            <i class="bi {{ $tuile['icone'] }} fs-4 opacity-75"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif

        {{-- Stagiaire --}}
        @if (isset($indicateurs['taches_a_faire']))
            @php
                $tuilesTaches = [
                    ['label' => 'Mes projets', 'valeur' => $indicateurs['mes_projets'], 'couleur' => '#4f46e5', 'icone' => 'bi-folder2-open'],
                    ['label' => 'À faire', 'valeur' => $indicateurs['taches_a_faire'], 'couleur' => '#6b7280', 'icone' => 'bi-circle'],
                    ['label' => 'En cours', 'valeur' => $indicateurs['taches_en_cours'], 'couleur' => '#d97706', 'icone' => 'bi-arrow-repeat'],
                    ['label' => 'En revue', 'valeur' => $indicateurs['taches_en_revue'], 'couleur' => '#0284c7', 'icone' => 'bi-eye'],
                    ['label' => 'Terminées', 'valeur' => $indicateurs['taches_terminees'], 'couleur' => '#16a34a', 'icone' => 'bi-check-circle'],
                    ['label' => 'Mes documents', 'valeur' => $indicateurs['mes_documents'], 'couleur' => '#0d9488', 'icone' => 'bi-file-earmark-text'],
                ];
            @endphp

            @foreach ($tuilesTaches as $tuile)
                <div class="col-6 col-lg-3">
                    <div class="stat-card" style="background:{{ $tuile['couleur'] }};">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-label">{{ $tuile['label'] }}</div>
                                <div class="stat-value">{{ $tuile['valeur'] }}</div>
                            </div>
                            <i class="bi {{ $tuile['icone'] }} fs-4 opacity-75"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@endsection
