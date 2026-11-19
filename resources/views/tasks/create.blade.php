@extends('layouts.app')

@section('title', 'Créer une tâche')

@section('content')
    <h1 class="page-title h3 mb-4"><i class="bi bi-list-check me-2"></i>Créer une tâche</h1>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-body p-4">
                    @if ($projects->isEmpty())
                        <p class="text-muted mb-0">Aucun projet disponible pour le moment. Créez d'abord un projet.</p>
                    @else
                        <form method="POST" action="{{ route('tasks.store') }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Projet</label>
                                <select name="project_id" id="projet-tache" class="form-select">
                                    @foreach ($projects as $project)
                                        <option value="{{ $project->id }}" @selected(($projetSelectionne?->id ?? old('project_id')) == $project->id)>
                                            {{ $project->nom }}@if ($project->groupe) — {{ $project->groupe->nom }}@elseif ($project->stage) — {{ $project->stage->stagiaire?->name ?? "Compte supprimé" }}@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Assigner à</label>
                                <select name="assigne_id" id="assigne-tache" class="form-select">
                                    <option value="">— Sélectionner un stagiaire —</option>
                                    @foreach ($projects as $project)
                                        @foreach ($project->stagiaires() as $stagiaire)
                                            <option value="{{ $stagiaire->id }}" data-projet="{{ $project->id }}" @selected((string) old('assigne_id') === (string) $stagiaire->id)>
                                                {{ $stagiaire->name }}
                                            </option>
                                        @endforeach
                                    @endforeach
                                </select>
                                <div class="form-text">Obligatoire pour un projet confié à un groupe : la tâche n'est visible que par le stagiaire choisi (et le chef de groupe).</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Titre</label>
                                <input type="text" name="titre" value="{{ old('titre') }}" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" rows="4" class="form-control">{{ old('description') }}</textarea>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Priorité</label>
                                    <select name="priorite" class="form-select">
                                        <option value="basse">Basse</option>
                                        <option value="moyenne" selected>Moyenne</option>
                                        <option value="haute">Haute</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Date d'échéance</label>
                                    <input type="date" name="date_echeance" value="{{ old('date_echeance') }}" class="form-control">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary mt-4">
                                <i class="bi bi-check-lg me-1"></i>Créer
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        // Confort d'interface : seuls les stagiaires du projet choisi sont proposés.
        // L'appartenance de l'assigné au projet est vérifiée côté serveur.
        (function () {
            var projet = document.getElementById('projet-tache');
            var assigne = document.getElementById('assigne-tache');
            if (!projet || !assigne) return;

            function actualiser() {
                Array.prototype.forEach.call(assigne.options, function (option) {
                    if (!option.value) return;
                    var visible = option.getAttribute('data-projet') === projet.value;
                    option.hidden = !visible;
                    option.disabled = !visible;
                });
                var courante = assigne.options[assigne.selectedIndex];
                if (courante && courante.disabled) assigne.value = '';
            }

            projet.addEventListener('change', actualiser);
            actualiser();
        })();
    </script>
@endsection
