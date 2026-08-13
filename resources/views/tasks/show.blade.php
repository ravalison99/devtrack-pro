<h1>{{ $task->titre }}</h1>
<p>Projet : {{ $task->project->nom }} — Priorité : {{ $task->priorite }} — Statut : {{ $task->statut }}</p>

@if (session('success'))
    <div style="color:green">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div style="color:red">{{ $errors->first() }}</div>
@endif

<h2>Commentaires</h2>
@foreach ($task->comments as $comment)
    <p><strong>{{ $comment->utilisateur->name }}</strong> : {{ $comment->contenu }}</p>
@endforeach

<h2>Pièces jointes</h2>
@foreach ($task->attachments as $attachment)
    <p>{{ $attachment->nom_fichier }}</p>
@endforeach

<h2>Ajouter une pièce jointe</h2>
<form method="POST" action="{{ route('tasks.attachments.store', $task) }}" enctype="multipart/form-data">
    @csrf
    <input type="file" name="fichier">
    <button type="submit">Envoyer</button>
</form>
