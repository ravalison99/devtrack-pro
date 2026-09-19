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
                            <strong>{{ $comment->utilisateur->name }}</strong>
                            <p class="mb-0 text-muted">{{ $comment->contenu }}</p>
                        </div>
                    @empty
                        <div class="list-group-item text-center text-muted py-4">Aucun commentaire pour le moment.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-paperclip me-1"></i>Pièces jointes</div>
                <div class="list-group list-group-flush">
                    @forelse ($task->attachments as $attachment)
                        <div class="list-group-item">
                            <i class="bi bi-file-earmark me-1"></i>{{ $attachment->nom_fichier }}
                        </div>
                    @empty
                        <div class="list-group-item text-center text-muted py-4">Aucune pièce jointe pour le moment.</div>
                    @endforelse
                </div>
                <div class="card-body border-top">
                    <form method="POST" action="{{ route('tasks.attachments.store', $task) }}" enctype="multipart/form-data" class="d-flex gap-2">
                        @csrf
                        <input type="file" name="fichier" class="form-control">
                        <button type="submit" class="btn btn-primary text-nowrap">
                            <i class="bi bi-upload me-1"></i>Envoyer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
