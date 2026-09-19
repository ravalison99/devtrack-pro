@php
    $currentUser = auth()->user();
@endphp

<nav class="navbar navbar-expand-lg navbar-devtrack navbar-dark sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
            <i class="bi bi-kanban-fill"></i> DevTrack Pro
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navPrincipale"
            aria-controls="navPrincipale" aria-expanded="false" aria-label="Basculer la navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navPrincipale">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="bi bi-speedometer2 me-1"></i>Tableau de bord
                    </a>
                </li>

                @if ($currentUser->isAdmin() || $currentUser->isMentor())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('stages.*') ? 'active' : '' }}" href="{{ route('stages.index') }}">
                            <i class="bi bi-mortarboard me-1"></i>Stages
                        </a>
                    </li>
                @endif

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('projects.*') ? 'active' : '' }}" href="{{ route('projects.index') }}">
                        <i class="bi bi-folder2-open me-1"></i>Projets
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}" href="{{ route('tasks.index') }}">
                        <i class="bi bi-list-check me-1"></i>Tâches
                    </a>
                </li>

                @if ($currentUser->isStagiaire())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('journal.*') ? 'active' : '' }}" href="{{ route('journal.index') }}">
                            <i class="bi bi-journal-text me-1"></i>Journal
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                            <i class="bi bi-file-earmark-bar-graph me-1"></i>Rapports
                        </a>
                    </li>
                @endif

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('documents.*') ? 'active' : '' }}" href="{{ route('documents.index') }}">
                        <i class="bi bi-file-earmark-text me-1"></i>Documents
                    </a>
                </li>

                @if ($currentUser->isAdmin())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                            <i class="bi bi-people me-1"></i>Utilisateurs
                        </a>
                    </li>
                @endif

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}">
                        <i class="bi bi-bell me-1"></i>Notifications
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <span class="text-white-50 small d-none d-lg-inline">
                    {{ $currentUser->name }}
                    <span class="badge rounded-pill bg-light text-dark text-uppercase ms-1">{{ $currentUser->role }}</span>
                </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>Déconnexion
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
