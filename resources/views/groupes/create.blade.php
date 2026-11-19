@extends('layouts.app')

@section('title', 'Créer un groupe')

@section('content')
    <h1 class="page-title h3 mb-4"><i class="bi bi-people me-2"></i>Créer un groupe</h1>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-body p-4">
                    @if ($stagiaires->isEmpty() || $projects->isEmpty())
                        <p class="text-muted mb-0">
                            @if ($stagiaires->isEmpty())
                                Aucun stagiaire disponible : tous les stagiaires actifs appartiennent déjà à un groupe.
                            @endif
                            @if ($projects->isEmpty())
                                Aucun projet non affecté disponible. <a href="{{ route('projects.create') }}">Créez d'abord un projet.</a>
                            @endif
                        </p>
                    @else
                        <form method="POST" action="{{ route('groupes.store') }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Nom du groupe</label>
                                <input type="text" name="nom" value="{{ old('nom') }}" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Stagiaires (jusqu'à {{ $maxMembres }})</label>
                                @for ($i = 0; $i < $maxMembres; $i++)
                                    <select name="membres[]" class="form-select mb-2 select-membre" @required($i === 0)>
                                        <option value="">— {{ $i === 0 ? 'Sélectionner un stagiaire' : 'Stagiaire facultatif' }} —</option>
                                        @foreach ($stagiaires as $stagiaire)
                                            <option value="{{ $stagiaire->id }}" @selected((string) old('membres.'.$i) === (string) $stagiaire->id)>
                                                {{ $stagiaire->name }} ({{ $stagiaire->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                @endfor
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Chef de groupe</label>
                                <select name="chef_id" id="chef-id" class="form-select" required>
                                    <option value="">— Sélectionner le chef parmi les membres —</option>
                                    @foreach ($stagiaires as $stagiaire)
                                        <option value="{{ $stagiaire->id }}" @selected((string) old('chef_id') === (string) $stagiaire->id)>
                                            {{ $stagiaire->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Le chef de groupe doit être l'un des stagiaires sélectionnés.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Projet</label>
                                <select name="project_id" class="form-select" required>
                                    <option value="">— Sélectionner un projet non affecté —</option>
                                    @foreach ($projects as $project)
                                        <option value="{{ $project->id }}" @selected((string) old('project_id') === (string) $project->id)>
                                            {{ $project->nom }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>Créer le groupe
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        // Confort d'interface uniquement : la liste des chefs possibles se limite aux membres choisis.
        // La règle « le chef est membre du groupe » est vérifiée côté serveur.
        (function () {
            var selects = document.querySelectorAll('.select-membre');
            var chef = document.getElementById('chef-id');
            if (!chef) return;

            function actualiser() {
                var choisis = Array.prototype.map.call(selects, function (s) { return s.value; }).filter(Boolean);
                Array.prototype.forEach.call(chef.options, function (option) {
                    if (!option.value) return;
                    var visible = choisis.indexOf(option.value) !== -1;
                    option.hidden = !visible;
                    option.disabled = !visible;
                });
                if (chef.value && choisis.indexOf(chef.value) === -1) chef.value = '';
            }

            Array.prototype.forEach.call(selects, function (s) { s.addEventListener('change', actualiser); });
            actualiser();
        })();
    </script>
@endsection
