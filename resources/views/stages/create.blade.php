<h1>Créer un stage</h1>

@if ($errors->any())
    <div style="color:red">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('stages.store') }}">
    @csrf

    <label>Stagiaire</label>
    <select name="stagiaire_id">
        @foreach ($stagiaires as $s)
            <option value="{{ $s->id }}">{{ $s->name }}</option>
        @endforeach
    </select>

    <label>Mentor</label>
    <select name="mentor_id">
        @foreach ($mentors as $m)
            <option value="{{ $m->id }}">{{ $m->name }}</option>
        @endforeach
    </select>

    <label>Date de début</label>
    <input type="date" name="date_debut">

    <label>Date de fin</label>
    <input type="date" name="date_fin">

    <label>Mode de travail</label>
    <select name="mode_travail">
        <option value="presentiel">Présentiel</option>
        <option value="hybride">Hybride</option>
        <option value="teletravail">Télétravail</option>
    </select>

    <button type="submit">Créer</button>
</form>
