<h1>Stages</h1>

@if (session('success'))
    <div style="color:green">{{ session('success') }}</div>
@endif

<a href="{{ route('stages.create') }}">Créer un stage</a>

<table>
    <tr><th>Stagiaire</th><th>Mentor</th><th>Statut</th></tr>
    @foreach ($stages as $stage)
        <tr>
            <td>{{ $stage->stagiaire->name }}</td>
            <td>{{ $stage->mentor->name }}</td>
            <td>{{ $stage->statut }}</td>
        </tr>
    @endforeach
</table>
