<h1>Mes rapports hebdomadaires</h1>

@if (session('success'))
    <div style="color:green">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div style="color:red">{{ $errors->first() }}</div>
@endif

<h2>Soumettre un rapport</h2>
<form method="POST" action="{{ route('reports.store') }}">
    @csrf
    <label>Semaine</label>
    <input type="number" name="semaine" min="1" max="12" value="{{ old('semaine') }}">

    <label>Contenu</label>
    <textarea name="contenu" rows="6">{{ old('contenu') }}</textarea>

    <button type="submit">Soumettre</button>
</form>

<h2>Historique</h2>
@forelse ($reports as $report)
    <div style="border:1px solid #ccc; padding:8px; margin-bottom:8px;">
        <strong>Semaine {{ $report->semaine }}</strong> — {{ $report->statut }}
        @if ($report->fichier_pdf)
            <a href="{{ route('reports.download', $report->id) }}">Télécharger le PDF</a>
        @endif
    </div>
@empty
    <p>Aucun rapport soumis pour le moment.</p>
@endforelse
