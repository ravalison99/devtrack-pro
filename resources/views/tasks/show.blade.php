@extends('layouts.app')

@section('title', $task->titre)

@section('content')
    @php
        $etapes = ['a_faire' => 'À faire', 'en_cours' => 'En cours', 'en_revue' => 'En revue', 'termine' => 'Terminé'];
        $icones = ['a_faire' => 'bi-circle', 'en_cours' => 'bi-arrow-repeat', 'en_revue' => 'bi-eye', 'termine' => 'bi-check-lg'];
        $ordre = array_keys($etapes);
        $indexCourant = array_search($task->statut, $ordre, true);
        $moi = auth()->id();
    @endphp

    <x-fil-ariane :items="[['Tâches', route('tasks.index')], [$task->titre]]" />

    {{-- ===== En-tête de la tâche ===== --}}
    <div class="card mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                <h1 class="page-title h3 mb-0">{{ $task->titre }}</h1>
                <div class="d-flex gap-2 flex-wrap">
                    <x-badge-priorite :priorite="$task->priorite" />
                    <x-badge-statut :statut="$task->statut" />
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-3 text-muted small mb-3">
                <span><i class="bi bi-folder2-open me-1"></i>Projet :
                    @can('view', $task->project)
                        <a href="{{ route('projects.show', $task->project_id) }}" class="fw-semibold">{{ $task->project->nom }}</a>
                    @else
                        <strong class="text-body">{{ $task->project->nom }}</strong>
                    @endcan
                </span>
                <span class="d-inline-flex align-items-center gap-2">
                    <i class="bi bi-person me-0"></i>Assignée à :
                    @if ($task->assigne)
                        <x-avatar :user="$task->assigne" taille="sm" /><strong class="text-body">{{ $task->assigne->name }}</strong>
                    @else
                        <strong class="text-body">Non assignée</strong>
                    @endif
                </span>
                <span>Échéance : <x-echeance :date="$task->date_echeance" :statut="$task->statut" /></span>
            </div>

            @if ($task->description)
                <p class="mb-4" style="white-space: pre-line">{{ $task->description }}</p>
            @endif

            {{-- Frise d'avancement --}}
            <ol class="etapes mb-3" aria-label="Avancement de la tâche">
                @foreach ($etapes as $cle => $libelle)
                    @php $position = array_search($cle, $ordre, true); @endphp
                    <li class="etape {{ $position < $indexCourant ? 'fait' : '' }} {{ $position === $indexCourant ? 'courante' : '' }}" @if ($position === $indexCourant) aria-current="step" @endif>
                        <span class="rond"><i class="bi {{ $position < $indexCourant ? 'bi-check-lg' : $icones[$cle] }}"></i></span>
                        {{ $libelle }}
                    </li>
                @endforeach
            </ol>

            {{-- Actions : uniquement les transitions autorisées --}}
            @if (count($transitions) > 0)
                <div class="d-flex flex-wrap gap-2 justify-content-center pt-2">
                    @foreach ($transitions as $transition)
                        @php $avance = array_search($transition, $ordre, true) > $indexCourant; @endphp
                        <form method="POST" action="{{ route('tasks.updateStatus', $task) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="statut" value="{{ $transition }}">
                            <button type="submit" class="btn btn-sm {{ $avance ? 'btn-primary' : 'btn-outline-secondary' }}">
                                <i class="bi {{ $avance ? 'bi-arrow-right-circle' : 'bi-arrow-counterclockwise' }} me-1"></i>{{ $avance ? 'Passer en' : 'Revenir à' }} « {{ $etapes[$transition] }} »
                            </button>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="row g-4">
        {{-- ===== Discussion ===== --}}
        <div class="col-12 col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-chat-left-text me-1"></i>Discussion</span>
                    <span class="pastille pastille-neutre">{{ $task->comments->count() }}</span>
                </div>

                @if ($task->comments->isEmpty())
                    <x-etat-vide icone="bi-chat-dots" titre="Aucun commentaire pour le moment"
                        texte="Posez une question ou donnez une nouvelle : toutes les personnes concernées sont prévenues." />
                @else
                    <div class="fil-discussion">
                        @foreach ($task->comments as $comment)
                            @php $estMoi = $comment->utilisateur_id === $moi; @endphp
                            <div class="message {{ $estMoi ? 'moi' : '' }}">
                                <x-avatar :user="$comment->utilisateur" />
                                <div class="bulle">
                                    <div class="bulle-meta">
                                        <strong>{{ $comment->utilisateur?->name ?? "Compte supprimé" }}</strong>
                                        @if ($comment->utilisateur)
                                            · <span class="text-uppercase">{{ $comment->utilisateur->role }}</span>
                                        @endif
                                        · {{ $comment->created_at->format('d/m/Y H:i') }}
                                    </div>
                                    <div class="bulle-texte">{{ $comment->contenu }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="card-body border-top">
                    <form method="POST" action="{{ route('tasks.comments.store', $task) }}">
                        @csrf
                        <textarea name="contenu" rows="3" maxlength="2000" class="form-control mb-2" placeholder="Écrire un message sur cette tâche…" required>{{ old('contenu') }}</textarea>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>Envoyer le message
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ===== Fichiers rendus ===== --}}
        <div class="col-12 col-lg-5">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-paperclip me-1"></i>Fichiers rendus</span>
                    <span class="pastille pastille-neutre">{{ $task->attachments->count() }}</span>
                </div>

                <div class="card-body border-bottom">
                    <form method="POST" action="{{ route('tasks.attachments.store', $task) }}" enctype="multipart/form-data">
                        @csrf
                        <label class="dropzone mb-3" data-dropzone>
                            <i class="bi bi-cloud-arrow-up"></i>
                            <span class="d-block fw-semibold text-body">Rendre mon travail</span>
                            <span class="d-block small">Glissez un fichier ici ou cliquez pour parcourir</span>
                            <span class="dz-nom d-block" data-dz-nom></span>
                            <input type="file" name="fichier" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" required>
                        </label>
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <span class="small text-muted">PDF, DOC, DOCX, PNG ou JPG — 5 Mo maximum.</span>
                            <button type="submit" class="btn btn-primary text-nowrap">
                                <i class="bi bi-upload me-1"></i>Rendre le fichier
                            </button>
                        </div>
                    </form>
                </div>

                <div class="list-group list-group-flush">
                    @forelse ($task->attachments as $attachment)
                        <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
                            <div style="min-width:0">
                                <div class="fw-semibold tronque"><i class="bi bi-file-earmark me-1"></i>{{ $attachment->nom_fichier }}</div>
                                <div class="text-muted small">
                                    {{ $attachment->created_at->format('d/m/Y H:i') }}
                                    @if ($attachment->utilisateur)
                                        — déposé par {{ $attachment->utilisateur->name }}
                                    @endif
                                </div>
                            </div>
                            <a href="{{ route('tasks.attachments.download', [$task, $attachment]) }}" class="btn btn-sm btn-outline-primary text-nowrap">
                                <i class="bi bi-download me-1"></i>Télécharger
                            </a>
                        </div>
                    @empty
                        <x-etat-vide icone="bi-files" titre="Aucun fichier rendu" texte="Le travail déposé apparaîtra ici." class="py-4" />
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
