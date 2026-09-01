<h1>Mes documents</h1>

@if (session('success'))
    <div style="color:green">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div style="color:red">{{ $errors->first() }}</div>
@endif

<h2>Déposer un document</h2>
<form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
    @csrf
    <label>Titre</label>
    <input type="text" name="titre" value="{{ old('titre') }}">

    <label>Catégorie</label>
    <input type="text" name="categorie" value="{{ old('categorie') }}">

    <label>Fichier</label>
    <input type="file" name="fichier">

    <button type="submit">Déposer</button>
</form>

<h2>Mes documents</h2>
@forelse ($documents as $document)
    <div style="border:1px solid #ccc; padding:8px; margin-bottom:8px;">
        <strong>{{ $document->titre }}</strong> ({{ $document->categorie ?? 'Sans catégorie' }})
        <ul>
            @foreach ($document->versions as $version)
                <li>
                    Version {{ $version->numero_version }}
                    <a href="{{ route('documents.download', [$document->id, $version->id]) }}">Télécharger</a>
                </li>
            @endforeach
        </ul>
    </div>
@empty
    <p>Aucun document déposé pour le moment.</p>
@endforelse
