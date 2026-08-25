<meta name="csrf-token" content="{{ csrf_token() }}">
<h1>Kanban — {{ $project->nom }}</h1>

<div style="display:flex; gap:16px;">
    @foreach ($colonnes as $statut => $tasksDeLaColonne)
        <div class="colonne" data-statut="{{ $statut }}" style="border:1px solid #ccc; padding:8px; width:220px; min-height:300px;">
            <h3>{{ ucfirst(str_replace('_', ' ', $statut)) }}</h3>

            @foreach ($tasksDeLaColonne as $task)
                <div class="carte" draggable="true" data-task-id="{{ $task->id }}"
                     style="border:1px solid #999; padding:8px; margin-bottom:8px; cursor:grab; background:#fff;">
                    {{ $task->titre }}
                </div>
            @endforeach
        </div>
    @endforeach
</div>

<script>
document.querySelectorAll('.carte').forEach(carte => {
    carte.addEventListener('dragstart', e => {
        e.dataTransfer.setData('text/plain', carte.dataset.taskId);
    });
});

document.querySelectorAll('.colonne').forEach(colonne => {
    colonne.addEventListener('dragover', e => e.preventDefault());

    colonne.addEventListener('drop', async e => {
        e.preventDefault();
        const taskId = e.dataTransfer.getData('text/plain');
        const nouveauStatut = colonne.dataset.statut;

        const response = await fetch(`/tasks/${taskId}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ statut: nouveauStatut }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            location.reload();
        } else {
            alert(data.message || 'Transition non autorisée.');
        }
    });
});
</script>

