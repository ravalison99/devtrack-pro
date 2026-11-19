@extends('layouts.app')

@section('title', $task->titre)

@section('content')
    @php
        $couleursPriorite = ['basse' => 'bg-secondary', 'moyenne' => 'bg-warning text-dark', 'haute' => 'bg-danger'];
        $couleursStatutTache = ['a_faire' => 'bg-secondary', 'en_cours' => 'bg-warning text-dark', 'en_revue' => 'bg-info text-dark', 'termine' => 'bg-success'];
    @endphp

    <a href="{{ route('tasks.index') }}" class="text-decoration-none small text-muted d-inline-block mb-2">
        <i class="bi bi-arrow-left me-1"></i>Retour aux tâches
    </a>

    <div class="card mb-4">
        <div class="card-body p-4">
            <h1 class="page-title h4 mb-2">{{ $task->titre }}</h1>
            <p class="mb-0 text-muted">Projet : <strong class="text-body">{{ $task->project->nom }}</strong></p>
            <p class="mb-0 text-muted">Assignée à : <strong class="text-body">{{ $task->assigne?->name ?? 'Non assignée' }}</strong></p>
            <div class="mt-2 d-flex gap-2">
                <span class="badge {{ $couleursPriorite[$task->priorite] ?? 'bg-secondary' }}">Priorité : {{ $task->priorite }}</span>
                <span class="badge {{ $couleursStatutTache[$task->statut] ?? 'bg-secondary' }}">Statut : {{ $task->statut }}</span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-chat-left-text me-1"></i>Commentaires</div>
                <div class="list-group list-group-flush">
                    @forelse ($task->comments as $comment)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-baseline">
                                <strong>
                                    {{ $comment->utilisateur?->name ?? "Compte supprimé" }}
                                    @if ($comment->utilisateur)
                                        <span class="badge rounded-pill bg-light text-dark border text-uppercase ms-1">{{ $comment->utilisateur->role }}</span>
                                    @endif
                                </strong>
                                <span class="text-muted small">{{ $comment->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <p class="mb-0 mt-1" style="white-space: pre-line">{{ $comment->contenu }}</p>
                        </div>
                    @empty
                        <div class="list-group-item text-center text-muted py-4">Aucun commentaire pour le moment.</div>
                    @endforelse
                </div>
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

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-paperclip me-1"></i>Pièces jointes</div>
                <div class="list-group list-group-flush">
                    @forelse ($task->attachments as $attachment)
                        <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
                            <div>
                                <i class="bi bi-file-earmark me-1"></i>{{ $attachment->nom_fichier }}
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
                        <div class="list-group-item text-center text-muted py-4">Aucune pièce jointe pour le moment.</div>
                    @endforelse
                </div>
                <div class="card-body border-top">
                    <form method="POST" action="{{ route('tasks.attachments.store', $task) }}" enctype="multipart/form-data" class="d-flex gap-2">
                        @csrf
                        <input type="file" name="fichier" class="form-control" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" required>
                        <button type="submit" class="btn btn-primary text-nowrap">
                            <i class="bi bi-upload me-1"></i>Rendre le fichier
                        </button>
                    </form>
                    <div class="form-text">PDF, DOC, DOCX, PNG ou JPG — 5 Mo maximum.</div>
                </div>
            </div>
        </div>
    </div>
@endsection
