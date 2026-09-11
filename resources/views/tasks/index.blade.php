<h1>Tâches</h1>

@if (session('success'))
    <div style="color:green">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div style="color:red">{{ $errors->first() }}</div>
@endif

<h2>Créer une tâche</h2>
<form method="POST" action="{{ route('tasks.store') }}">
    @csrf
    <label>Projet</label>
    <select name="project_id">
        @foreach ($projects as $project)
            <option value="{{ $project->id }}">{{ $project->nom }}</option>
        @endforeach
    </select>

    <label>Titre</label>
    <input type="text" name="titre">

    <label>Priorité</label>
    <select name="priorite">
        <option value="basse">Basse</option>
        <option value="moyenne" selected>Moyenne</option>
        <option value="haute">Haute</option>
    </select>

    <label>Date d'échéance (optionnel)</label>
    <input type="date" name="date_echeance">

    <button type="submit">Créer</button>
</form>

<table>
    <tr><th>Titre</th><th>Projet</th><th>Priorité</th><th>Statut</th><th>Actions</th></tr>
    @foreach ($tasks as $task)
        <tr>
            <td><a href="{{ route('tasks.show', $task) }}">{{ $task->titre }}</a></td>
            <td>{{ $task->project->nom }}</td>
            <td>{{ $task->priorite }}</td>
            <td>{{ $task->statut }}</td>
            <td>
                <form method="POST" action="{{ route('tasks.updateStatus', $task) }}">
                    @csrf
                    @method('PATCH')
                    <select name="statut" onchange="this.form.submit()">
                        <option value="a_faire" @selected($task->statut === 'a_faire')>À faire</option>
                        <option value="en_cours" @selected($task->statut === 'en_cours')>En cours</option>
                        <option value="en_revue" @selected($task->statut === 'en_revue')>En revue</option>
                        <option value="termine" @selected($task->statut === 'termine')>Terminé</option>
                    </select>
                </form>
            </td>
        </tr>
    @endforeach
</table>
