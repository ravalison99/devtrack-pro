<h1>Projets</h1>

@if (session('success'))
    <div style="color:green">{{ session('success') }}</div>
@endif

<table>
    <tr><th>Nom</th><th>Stage</th><th>Archivé</th></tr>
    @foreach ($projects as $project)
        <tr>
            <td>{{ $project->nom }}</td>
            <td>{{ $project->stage->stagiaire->name }}</td>
            <td>{{ $project->archive ? 'Oui' : 'Non' }}</td>
        </tr>
    @endforeach
</table>
