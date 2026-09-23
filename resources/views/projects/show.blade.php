@extends('layouts.app')

@section('title', $project->nom)

@section('content')
    <a href="{{ route('projects.index') }}" class="text-decoration-none small text-muted d-inline-block mb-2">
        <i class="bi bi-arrow-left me-1"></i>Retour aux projets
    </a>

    <h1 class="page-title h3 mb-1"><i class="bi bi-folder2-open me-2"></i>{{ $project->nom }}</h1>
    @if ($project->description)
        <p class="text-muted mb-4">{{ $project->description }}</p>
    @endif

    @php
        $estMentorDuProjet = auth()->user()->isMentor() && auth()->id() === $project->stage->mentor_id;
        $libellesStatut = ['a_faire' => 'À faire', 'en_cours' => 'En cours', 'en_revue' => 'En revue', 'termine' => 'Terminé'];
    @endphp

    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-file-earmark-text me-1"></i>Documents du projet</div>
                <div class="list-group list-group-flush">
                    @forelse ($documents as $document)
                        <div class="list-group-item">
                            <strong class="d-block mb-1">{{ $document->titre }}</strong>
                            <ul class="list-unstyled mb-0 small">
                                @foreach ($document->versions as $version)
                                    <li class="d-flex justify-content-between align-items-center py-1">
                                        <span>Version {{ $version->numero_version }}</span>
                                        <a href="{{ route('documents.download', [$document->id, $version->id]) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download me-1"></i>Télécharger
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @empty
                        <div class="list-group-item text-center text-muted py-4">Aucun document déposé pour ce projet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-list-check me-1"></i>Tâches du projet</div>
                <div class="list-group list-group-flush">
                    @forelse ($tasks as $task)
                        @php $derniereAttachment = $task->attachments->sortByDesc('id')->first(); @endphp
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                {{ $task->titre }}
                                <span class="badge bg-secondary ms-1">{{ $libellesStatut[$task->statut] ?? $task->statut }}</span>
                            </span>
                            <div class="d-flex gap-2">
                                <a href="{{ route('tasks.show', $task) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i>Voir
                                </a>
                                @if ($derniereAttachment)
                                    <a href="{{ route('tasks.attachments.download', [$task, $derniereAttachment]) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-download me-1"></i>Télécharger
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="list-group-item text-center text-muted py-4">Aucune tâche pour ce projet.</div>
                    @endforelse
                </div>
            </div>

            @if ($estMentorDuProjet)
                <div class="card">
                    <div class="card-header"><i class="bi bi-plus-square me-1"></i>Créer plusieurs tâches</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('tasks.storeBatch', $project) }}">
                            @csrf

                            <div id="lignes-taches">
                                <div class="row g-2 align-items-end mb-2 ligne-tache">
                                    <div class="col-7">
                                        <label class="form-label small">Titre</label>
                                        <input type="text" name="taches[0][titre]" class="form-control form-control-sm" required>
                                    </div>
                                    <div class="col-5">
                                        <label class="form-label small">Priorité</label>
                                        <select name="taches[0][priorite]" class="form-select form-select-sm" required>
                                            <option value="basse">Basse</option>
                                            <option value="moyenne" selected>Moyenne</option>
                                            <option value="haute">Haute</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <button type="button" id="ajouter-ligne" class="btn btn-sm btn-outline-secondary mb-3">
                                <i class="bi bi-plus-lg me-1"></i>Ajouter une ligne
                            </button>

                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-1"></i>Créer les tâches
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let compteurLignes = 1;

        document.getElementById('ajouter-ligne')?.addEventListener('click', function () {
            const conteneur = document.getElementById('lignes-taches');
            const nouvelleLigne = conteneur.querySelector('.ligne-tache').cloneNode(true);

            nouvelleLigne.querySelectorAll('input, select').forEach(function (champ) {
                champ.name = champ.name.replace(/taches\[\d+\]/, `taches[${compteurLignes}]`);
                champ.value = champ.tagName === 'SELECT' ? 'moyenne' : '';
            });

            conteneur.appendChild(nouvelleLigne);
            compteurLignes++;
        });
    </script>
@endpush
