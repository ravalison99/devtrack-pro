@php
    $currentUser = auth()->user();
    $nombreNonLues = $currentUser->unreadNotifications()->count();

    // « Mon projet » : le projet attribué au stagiaire (groupe ou stage historique), à défaut la liste des projets.
    $lienMonProjet = route('projects.index');
    if ($currentUser->isStagiaire()) {
        $idMonProjet = \App\Models\Project::duStagiaire($currentUser->id)->orderByDesc('id')->value('id');
        $lienMonProjet = $idMonProjet ? route('projects.show', $idMonProjet) : route('projects.index');
    }
@endphp

<nav class="navbar navbar-expand-lg navbar-devtrack navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
            <i class="bi bi-kanban-fill"></i> DevTrack Pro
        </a>

        <div class="d-flex align-items-center gap-2 order-lg-last">
            <button type="button" class="btn-nav" data-theme-toggle aria-pressed="false" title="Changer de thème">
                <i class="bi bi-moon-stars-fill"></i><span class="visually-hidden">Changer de thème</span>
            </button>

            <a href="{{ route('notifications.index') }}" class="btn-nav" title="Notifications">
                <i class="bi bi-bell-fill"></i>
                <span class="visually-hidden">Notifications</span>
                @if ($nombreNonLues > 0)
                    <span class="badge-cloche">{{ $nombreNonLues > 99 ? '99+' : $nombreNonLues }}</span>
                @endif
            </a>

            <div class="dropdown">
                <button class="btn p-0 border-0 d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu utilisateur">
                    <x-avatar :user="$currentUser" taille="lg" />
                </button>
                <ul class="dropdown-menu dropdown-menu-end menu-utilisateur p-2">
                    <li class="px-2 py-2 d-flex align-items-center gap-2">
                        <x-avatar :user="$currentUser" />
                        <div class="lh-sm">
                            <div class="fw-semibold">{{ $currentUser->name }}</div>
                            <span class="badge rounded-pill bg-light text-dark border text-uppercase" style="font-size:.62rem">{{ $currentUser->role }}</span>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item rounded-3" href="{{ route('notifications.index') }}">
                            <i class="bi bi-bell me-2"></i>Notifications
                            @if ($nombreNonLues > 0)<span class="badge rounded-pill bg-danger ms-1">{{ $nombreNonLues }}</span>@endif
                        </a>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item rounded-3" data-theme-toggle>
                            <i class="bi bi-moon-stars-fill me-2"></i><span data-theme-libelle>Mode sombre</span>
                        </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item rounded-3 text-danger">
                                <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
                            </button>
                        </form>
                    </li>
                </ul>
            </div>

            <button class="navbar-toggler ms-1" type="button" data-bs-toggle="collapse" data-bs-target="#navPrincipale"
                aria-controls="navPrincipale" aria-expanded="false" aria-label="Basculer la navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <div class="collapse navbar-collapse" id="navPrincipale">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3 gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="bi bi-house-door me-1"></i>{{ $currentUser->isStagiaire() ? 'Accueil' : 'Tableau de bord' }}
                    </a>
                </li>

                @if ($currentUser->isAdmin() || $currentUser->isMentor())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('stages.*') ? 'active' : '' }}" href="{{ route('stages.index') }}">
                            <i class="bi bi-mortarboard me-1"></i>Stages
                        </a>
                    </li>
                @endif

                @if ($currentUser->isStagiaire())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('projects.*', 'kanban.*') ? 'active' : '' }}" href="{{ $lienMonProjet }}">
                            <i class="bi bi-folder2-open me-1"></i>Mon projet
                        </a>
                    </li>
                @else
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('projects.*', 'kanban.*') ? 'active' : '' }}" href="{{ route('projects.index') }}">
                            <i class="bi bi-folder2-open me-1"></i>Projets
                        </a>
                    </li>
                @endif

                @if ($currentUser->isMentor())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('groupes.*') ? 'active' : '' }}" href="{{ route('groupes.index') }}">
                            <i class="bi bi-people me-1"></i>Groupes
                        </a>
                    </li>
                @endif

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}" href="{{ route('tasks.index') }}">
                        <i class="bi bi-list-check me-1"></i>{{ $currentUser->isStagiaire() ? 'Mes tâches' : 'Tâches' }}
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('documents.*') ? 'active' : '' }}" href="{{ route('documents.index') }}">
                        <i class="bi bi-file-earmark-text me-1"></i>Documents
                    </a>
                </li>

                @if ($currentUser->isStagiaire())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('journal.*') ? 'active' : '' }}" href="{{ route('journal.index') }}">
                            <i class="bi bi-journal-text me-1"></i>Journal
                        </a>
                    </li>
                @endif

                @if ($currentUser->isAdmin())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                            <i class="bi bi-people me-1"></i>Utilisateurs
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</nav>

{{-- Téléphone : barre de navigation basse, à portée de pouce (stagiaires). --}}
@if ($currentUser->isStagiaire())
    <nav class="nav-mobile d-lg-none" aria-label="Navigation principale">
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="bi bi-house-door"></i>Accueil</a>
        <a href="{{ $lienMonProjet }}" class="{{ request()->routeIs('projects.*', 'kanban.*') ? 'active' : '' }}"><i class="bi bi-folder2-open"></i>Projet</a>
        <a href="{{ route('tasks.index') }}" class="{{ request()->routeIs('tasks.*') ? 'active' : '' }}"><i class="bi bi-list-check"></i>Tâches</a>
        <a href="{{ route('documents.index') }}" class="{{ request()->routeIs('documents.*') ? 'active' : '' }}"><i class="bi bi-file-earmark-text"></i>Documents</a>
        <a href="{{ route('journal.index') }}" class="{{ request()->routeIs('journal.*') ? 'active' : '' }}"><i class="bi bi-journal-text"></i>Journal</a>
    </nav>
@endif
