@extends('layouts.app')

@section('title', 'Utilisateurs')

@section('content')
    <h1 class="page-title h3 mb-4"><i class="bi bi-people me-2"></i>Utilisateurs</h1>

    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-header"><i class="bi bi-person-plus me-1"></i>Créer un utilisateur</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('users.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mot de passe</label>
                            <input type="password" name="password" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Rôle</label>
                            <select name="role" class="form-select">
                                <option value="admin">Admin</option>
                                <option value="mentor">Mentor</option>
                                <option value="stagiaire" selected>Stagiaire</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Créer
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-header"><i class="bi bi-list-ul me-1"></i>Liste des utilisateurs</div>
                <div class="card-body border-bottom">
                    <form method="GET" action="{{ route('users.index') }}" id="formRechercheUtilisateurs" class="d-flex gap-2">
                        <input type="search" name="search" id="rechercheUtilisateur" value="{{ $recherche }}"
                            placeholder="Rechercher un utilisateur (nom, e-mail, rôle)..." class="form-control form-control-sm">
                        <button type="submit" class="btn btn-sm btn-outline-secondary text-nowrap">
                            <i class="bi bi-search me-1"></i>Rechercher
                        </button>
                    </form>
                </div>

                <div id="tableauUtilisateurs">
                    @include('users._table', compact('utilisateurs', 'champ', 'direction'))
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const champRecherche = document.getElementById('rechercheUtilisateur');
            const formulaire = document.getElementById('formRechercheUtilisateurs');
            const conteneur = document.getElementById('tableauUtilisateurs');
            let minuteur = null;

            async function rechercher() {
                const params = new URLSearchParams({ search: champRecherche.value });

                const response = await fetch(`{{ route('users.index') }}?${params.toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (response.ok) {
                    conteneur.innerHTML = await response.text();
                    history.replaceState(null, '', `{{ route('users.index') }}?${params.toString()}`);
                }
            }

            champRecherche.addEventListener('input', () => {
                clearTimeout(minuteur);
                minuteur = setTimeout(rechercher, 350);
            });

            formulaire.addEventListener('submit', (e) => {
                e.preventDefault();
                clearTimeout(minuteur);
                rechercher();
            });
        })();
    </script>
@endpush
