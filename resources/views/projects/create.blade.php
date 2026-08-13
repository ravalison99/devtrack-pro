<h1>Créer un projet pour le stage #{{ $stage->id }}</h1>
<p>Stagiaire : {{ $stage->stagiaire->name }} — Mentor : {{ $stage->mentor->name }}</p>

@if ($errors->any())
    <div style="color:red">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('projects.store') }}">
    @csrf
    <input type="hidden" name="stage_id" value="{{ $stage->id }}">

    <label>Nom du projet</label>
    <input type="text" name="nom" value="{{ old('nom') }}">

    <label>Description</label>
    <textarea name="description">{{ old('description') }}</textarea>

    <button type="submit">Créer</button>
</form>
