<h1>Mon journal quotidien</h1>

@if (session('success'))
    <div style="color:green">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div style="color:red">{{ $errors->first() }}</div>
@endif

<h2>Nouvelle entrée</h2>
<form method="POST" action="{{ route('journal.store') }}">
    @csrf
    <label>Date</label>
    <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}">

    <label>Contenu</label>
    <textarea name="contenu" rows="4">{{ old('contenu') }}</textarea>

    <button type="submit">Enregistrer</button>
</form>

<h2>Historique</h2>
@forelse ($entries as $entry)
    <div style="border:1px solid #ccc; padding:8px; margin-bottom:8px;">
        <strong>{{ $entry->date->format('d/m/Y') }}</strong>
        <p>{{ $entry->contenu }}</p>
    </div>
@empty
    <p>Aucune entrée pour le moment.</p>
@endforelse
