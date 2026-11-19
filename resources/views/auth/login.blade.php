@extends('layouts.guest')

@section('title', 'Connexion')

@section('content')
    <div class="connexion">
        {{-- Présentation --}}
        <aside class="connexion-hero">
            <div class="d-flex align-items-center gap-2 fs-5 fw-bold">
                <i class="bi bi-kanban-fill"></i> DevTrack Pro
            </div>

            <div>
                <h2 class="display-6 fw-bold mb-3">Suivez votre stage,<br>sans vous perdre.</h2>
                <p class="mb-4 opacity-75" style="max-width: 28rem">Projets, tâches, documents et journal au même endroit, pour avancer en équipe avec votre mentor.</p>

                <div class="point"><i class="bi bi-list-check"></i><div><strong>Des tâches claires</strong><div class="small opacity-75">Sachez toujours quoi faire en premier et pour quand.</div></div></div>
                <div class="point"><i class="bi bi-people"></i><div><strong>Un travail d'équipe</strong><div class="small opacity-75">Groupes, discussions et fichiers partagés avec le chef de groupe.</div></div></div>
                <div class="point"><i class="bi bi-graph-up-arrow"></i><div><strong>Une progression visible</strong><div class="small opacity-75">Suivez l'avancement de vos tâches et de votre projet.</div></div></div>
            </div>

            <div class="small opacity-75">© {{ date('Y') }} DevTrack Pro</div>
        </aside>

        {{-- Formulaire --}}
        <main class="connexion-formulaire">
            <button type="button" class="btn btn-outline-secondary bouton-theme-flottant" data-theme-toggle aria-pressed="false" title="Changer de thème">
                <i class="bi bi-moon-stars-fill"></i><span class="visually-hidden">Changer de thème</span>
            </button>

            <div class="carte-connexion">
                <div class="mb-4">
                    <div class="connexion-marque mb-3"><i class="bi bi-kanban-fill"></i></div>
                    <h1 class="h3 fw-bold mb-1">Bon retour 👋</h1>
                    <p class="text-muted mb-0">Connectez-vous pour accéder à votre espace.</p>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger py-2 small" role="alert">
                        <i class="bi bi-exclamation-triangle me-1"></i>{{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="/login">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input id="email" type="email" name="email" value="{{ old('email') }}"
                                class="form-control form-control-lg" placeholder="vous@exemple.com" autocomplete="email" autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input id="password" type="password" name="password" class="form-control form-control-lg" placeholder="••••••••" autocomplete="current-password">
                            <button type="button" class="btn btn-outline-secondary" data-voir-mdp="#password" aria-label="Afficher ou masquer le mot de passe">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Se connecter
                    </button>
                </form>
            </div>
        </main>
    </div>
@endsection
