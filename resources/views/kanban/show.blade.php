@extends('layouts.app')

@section('title', 'Kanban — ' . $project->nom)

@section('content')
    <x-fil-ariane :items="[['Projets', route('projects.index')], [$project->nom, route('projects.show', $project->id)], ['Kanban']]" />

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h1 class="page-title h3 mb-0"><i class="bi bi-kanban me-2"></i>Kanban — {{ $project->nom }}</h1>
        <a href="{{ route('projects.show', $project->id) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Retour au projet
        </a>
    </div>

    @php
        $intitulesColonnes = [
            'a_faire' => ['À faire', 'bi-circle'],
            'en_cours' => ['En cours', 'bi-arrow-repeat'],
            'en_revue' => ['En revue', 'bi-eye'],
            'termine' => ['Terminé', 'bi-check-circle'],
        ];
    @endphp

    <div class="kanban-board">
        @foreach ($colonnes as $statut => $tasksDeLaColonne)
            <div class="kanban-colonne" data-statut="{{ $statut }}">
                <h6 class="text-uppercase text-muted mb-3 d-flex justify-content-between align-items-center">
                    <span><i class="bi {{ $intitulesColonnes[$statut][1] ?? 'bi-circle' }} me-1"></i>{{ $intitulesColonnes[$statut][0] ?? $statut }}</span>
                    <span class="pastille pastille-neutre">{{ $tasksDeLaColonne->count() }}</span>
                </h6>

                @foreach ($tasksDeLaColonne as $task)
                    @php $transitions = $transitionsParTache[$task->id] ?? []; @endphp
                    <div
                        class="kanban-carte prio-{{ $task->priorite }} {{ count($transitions) === 0 ? 'kanban-carte-verrouillee' : '' }}"
                        draggable="{{ count($transitions) > 0 ? 'true' : 'false' }}"
                        data-task-id="{{ $task->id }}"
                        data-transitions="{{ implode(',', $transitions) }}"
                    >
                        <a href="{{ route('tasks.show', $task) }}" class="lien-tache d-block mb-2" draggable="false">{{ $task->titre }}</a>
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <x-echeance :date="$task->date_echeance" :statut="$task->statut" />
                            @if ($task->assigne)
                                <x-avatar :user="$task->assigne" taille="sm" />
                            @endif
                        </div>
                    </div>
                @endforeach

                @if ($tasksDeLaColonne->isEmpty())
                    <div class="text-center text-muted small py-4">Aucune tâche</div>
                @endif
            </div>
        @endforeach
    </div>
@endsection

@push('scripts')
    <script>
        let transitionsAutoriseesCourantes = [];

        document.querySelectorAll('.kanban-carte').forEach(carte => {
            carte.addEventListener('dragstart', e => {
                if (carte.draggable === false || carte.getAttribute('draggable') === 'false') {
                    e.preventDefault();
                    return;
                }
                transitionsAutoriseesCourantes = (carte.dataset.transitions || '').split(',').filter(Boolean);
                e.dataTransfer.setData('text/plain', carte.dataset.taskId);
            });
        });

        document.querySelectorAll('.kanban-colonne').forEach(colonne => {
            colonne.addEventListener('dragover', e => {
                if (!transitionsAutoriseesCourantes.includes(colonne.dataset.statut)) {
                    return;
                }
                e.preventDefault();
                colonne.classList.add('drag-over');
            });

            colonne.addEventListener('dragleave', () => colonne.classList.remove('drag-over'));

            colonne.addEventListener('drop', async e => {
                if (!transitionsAutoriseesCourantes.includes(colonne.dataset.statut)) {
                    return;
                }

                e.preventDefault();
                colonne.classList.remove('drag-over');

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
@endpush
