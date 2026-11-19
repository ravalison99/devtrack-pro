@extends('layouts.app')

@section('title', auth()->user()->isStagiaire() ? 'Accueil' : 'Tableau de bord')

@section('content')
    @if (auth()->user()->isStagiaire())
        @include('dashboard.stagiaire')
    @else
        @php
            $heure = now()->hour;
            $salutation = $heure < 6 ? 'Bonne nuit' : ($heure < 18 ? 'Bonjour' : 'Bonsoir');
            $prenom = \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first();
        @endphp

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h1 class="page-title h3 mb-1">{{ $salutation }}, {{ $prenom }} 👋</h1>
                <p class="text-muted mb-0">Tableau de bord {{ auth()->user()->isAdmin() ? 'de l\'administration' : 'du mentor' }}</p>
            </div>

            @if (auth()->user()->isMentor())
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('projects.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-folder-plus me-1"></i>Créer un projet
                    </a>
                    <a href="{{ route('groupes.create') }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-people me-1"></i>Créer un groupe
                    </a>
                    <a href="{{ route('tasks.create') }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i>Créer une tâche
                    </a>
                </div>
            @endif
        </div>

        @php $tuiles = []; @endphp

        <div class="row g-3">
            {{-- Admin --}}
            @if (isset($indicateurs['total_utilisateurs']))
                @php
                    $tuiles = [
                        ['label' => 'Utilisateurs', 'valeur' => $indicateurs['total_utilisateurs'], 'couleur' => '#4f46e5', 'icone' => 'bi-people', 'lien' => route('users.index')],
                        ['label' => 'Stagiaires', 'valeur' => $indicateurs['total_stagiaires'], 'couleur' => '#0284c7', 'icone' => 'bi-person', 'lien' => route('users.index')],
                        ['label' => 'Mentors', 'valeur' => $indicateurs['total_mentors'], 'couleur' => '#0d9488', 'icone' => 'bi-person-badge', 'lien' => route('users.index')],
                        ['label' => 'Stages au total', 'valeur' => $indicateurs['total_stages'], 'couleur' => '#6b7280', 'icone' => 'bi-mortarboard', 'lien' => route('stages.index')],
                        ['label' => 'Stages en cours', 'valeur' => $indicateurs['stages_en_cours'], 'couleur' => '#4338ca', 'icone' => 'bi-mortarboard-fill', 'lien' => route('stages.index')],
                        ['label' => 'Stages terminés', 'valeur' => $indicateurs['stages_termines'], 'couleur' => '#16a34a', 'icone' => 'bi-check-circle', 'lien' => route('stages.index')],
                        ['label' => 'Stages annulés', 'valeur' => $indicateurs['stages_annules'], 'couleur' => '#dc2626', 'icone' => 'bi-x-circle', 'lien' => route('stages.index')],
                        ['label' => 'Projets', 'valeur' => $indicateurs['total_projets'], 'couleur' => '#d97706', 'icone' => 'bi-folder2-open', 'lien' => route('projects.index')],
                        ['label' => 'Tâches', 'valeur' => $indicateurs['total_taches'], 'couleur' => '#0284c7', 'icone' => 'bi-list-check', 'lien' => route('tasks.index')],
                    ];
                @endphp
            @endif

            {{-- Mentor --}}
            @if (isset($indicateurs['mes_stagiaires']))
                @php
                    $tuiles = [
                        ['label' => 'Stagiaires encadrés', 'valeur' => $indicateurs['mes_stagiaires'], 'couleur' => '#4f46e5', 'icone' => 'bi-people', 'lien' => route('stages.index')],
                        ['label' => 'Mes stages', 'valeur' => $indicateurs['mes_stages'], 'couleur' => '#6b7280', 'icone' => 'bi-mortarboard', 'lien' => route('stages.index')],
                        ['label' => 'Stages en cours', 'valeur' => $indicateurs['stages_en_cours'], 'couleur' => '#4338ca', 'icone' => 'bi-mortarboard-fill', 'lien' => route('stages.index')],
                        ['label' => 'Mes projets', 'valeur' => $indicateurs['mes_projets'], 'couleur' => '#d97706', 'icone' => 'bi-folder2-open', 'lien' => route('projects.index')],
                        ['label' => 'Mes tâches', 'valeur' => $indicateurs['mes_taches'], 'couleur' => '#0284c7', 'icone' => 'bi-list-check', 'lien' => route('tasks.index')],
                        ['label' => 'Tâches en cours', 'valeur' => $indicateurs['taches_en_cours'], 'couleur' => '#d97706', 'icone' => 'bi-arrow-repeat', 'lien' => route('tasks.index', ['statut' => 'en_cours'])],
                        ['label' => 'Tâches terminées', 'valeur' => $indicateurs['taches_terminees'], 'couleur' => '#16a34a', 'icone' => 'bi-check-circle', 'lien' => route('tasks.index', ['statut' => 'termine'])],
                        ['label' => 'Tâches en retard', 'valeur' => $indicateurs['taches_en_retard'], 'couleur' => '#dc2626', 'icone' => 'bi-exclamation-triangle', 'lien' => route('tasks.index', ['retard' => 1])],
                    ];
                @endphp
            @endif

            @foreach ($tuiles as $tuile)
                <div class="col-6 col-lg-3">
                    <x-stat :label="$tuile['label']" :valeur="$tuile['valeur']" :icone="$tuile['icone']" :ton="$tuile['couleur']" :lien="$tuile['lien']" />
                </div>
            @endforeach
        </div>
    @endif
@endsection
