@extends('layouts.app')

@section('title', 'Kanban — ' . $project->nom)

@push('styles')
    <style>
        .kanban-board {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            padding-bottom: .5rem;
        }

        .kanban-colonne {
            background: #eef0f6;
            border-radius: .9rem;
            padding: .9rem;
            min-width: 260px;
            flex: 1 1 260px;
        }

        .kanban-colonne.drag-over {
            background: #e0e4f5;
        }

        .kanban-carte {
            background: #fff;
            border-radius: .6rem;
            padding: .75rem .9rem;
            margin-bottom: .6rem;
            box-shadow: 0 .1rem .4rem rgba(31, 41, 55, .08);
            cursor: grab;
        }

        .kanban-carte:active {
            cursor: grabbing;
        }

        .kanban-carte-verrouillee {
            cursor: not-allowed;
            opacity: .65;
        }
    </style>
@endpush

@section('content')
    <a href="{{ route('projects.index') }}" class="text-decoration-none small text-muted d-inline-block mb-2">
        <i class="bi bi-arrow-left me-1"></i>Retour aux projets
    </a>

    <h1 class="page-title h3 mb-4"><i class="bi bi-kanban me-2"></i>Kanban — {{ $project->nom }}</h1>

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
                <h6 class="text-uppercase text-muted mb-3">
                    <i class="bi {{ $intitulesColonnes[$statut][1] ?? 'bi-circle' }} me-1"></i>
                    {{ $intitulesColonnes[$statut][0] ?? $statut }}
                    <span class="badge bg-secondary rounded-pill float-end">{{ $tasksDeLaColonne->count() }}</span>
                </h6>

                @foreach ($tasksDeLaColonne as $task)
                    @php $transitions = $transitionsParTache[$task->id] ?? []; @endphp
                    <div
                        class="kanban-carte {{ count($transitions) === 0 ? 'kanban-carte-verrouillee' : '' }}"
                        draggable="{{ count($transitions) > 0 ? 'true' : 'false' }}"
                        data-task-id="{{ $task->id }}"
                        data-transitions="{{ implode(',', $transitions) }}"
                    >
                        {{ $task->titre }}
                    </div>
                @endforeach
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
